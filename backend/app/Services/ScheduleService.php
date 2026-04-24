<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 排课核心服务。
 *
 * 规则：
 *  - holiday 类型的节假日：永远跳过，不生成课次
 *  - workday 类型（调休补班）：不再被当作节假日，其余走 weekly_days 判断
 *  - weekly_days：ISO 1..7（1=周一、7=周日）
 *  - periods_per_day：数组，长度即为该日开课节次数；period 字段记录其 1-based 索引
 *  - seq：course_sessions.seq 在同一 course 内从 1 起连续编号
 */
class ScheduleService
{
    /**
     * 从零生成课次（要求课程当前无课次，否则请调用 regenerate）。
     *
     * @return Collection<int, CourseSession>
     */
    public function generate(Course $course): Collection
    {
        $created = DB::transaction(function () use ($course) {
            $existing = $course->sessions()->count();
            if ($existing > 0) {
                throw new \DomainException(
                    "课程已存在 {$existing} 条课次，请改用 regenerate。"
                );
            }

            $rows = $this->buildSessionRows($course, startSeq: 1);
            if ($rows === []) {
                return collect();
            }

            CourseSession::insert($rows);

            return $course->sessions()->orderBy('seq')->get();
        });

        if ($created->isNotEmpty()) {
            $this->markAssignmentReminder($course);

            return $course->sessions()->orderBy('seq')->get();
        }

        return $created;
    }

    /**
     * 重算课次：保留 completed / cancelled / skipped / 已有考勤记录的课次，
     * 仅删除仍为 scheduled 且无考勤的课次，然后从保留课次最大 seq+1 续排。
     *
     * @return Collection<int, CourseSession>
     */
    public function regenerate(Course $course): Collection
    {
        DB::transaction(function () use ($course): void {
            $course->sessions()
                ->where('status', 'scheduled')
                ->whereDoesntHave('attendances')
                ->delete();

            $maxSeq = (int) $course->sessions()->max('seq');
            $keptSlots = $course->sessions()
                ->get(['session_date', 'period'])
                ->map(function ($s): string {
                    $d = $s->session_date instanceof \DateTimeInterface
                        ? $s->session_date->format('Y-m-d')
                        : (string) $s->session_date;

                    return $d.'#'.$s->period;
                })
                ->all();

            $rows = $this->buildSessionRows(
                $course,
                startSeq: $maxSeq + 1,
                excludeSlots: $keptSlots,
            );

            if ($rows !== []) {
                CourseSession::insert($rows);
            }
        });

        $this->markAssignmentReminder($course);

        return $course->sessions()->orderBy('seq')->get();
    }

    /**
     * 将 fromDate 当天该课程的所有 scheduled 课次整体挪到 toDate。
     * toDate 命中 holiday 类型节假日时拒绝移动。
     */
    public function shift(Course $course, string $fromDate, string $toDate): void
    {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($from->equalTo($to)) {
            return;
        }

        $holiday = Holiday::whereDate('date', $to->toDateString())
            ->where('type', Holiday::TYPE_HOLIDAY)
            ->first();
        if ($holiday) {
            throw new \DomainException(
                "目标日期 {$to->toDateString()} 为法定节假日（{$holiday->name}），禁止调入。"
            );
        }

        DB::transaction(function () use ($course, $from, $to): void {
            $sessions = $course->sessions()
                ->whereDate('session_date', $from->toDateString())
                ->where('status', 'scheduled')
                ->orderBy('period')
                ->get();

            if ($sessions->isEmpty()) {
                throw new \DomainException(
                    "{$from->toDateString()} 无可调整的 scheduled 课次。"
                );
            }

            foreach ($sessions as $session) {
                $session->update([
                    'session_date' => $to->toDateString(),
                    'weekday' => $to->dayOfWeekIso,
                    'remark' => trim(($session->remark ?? '')."\n[调课] {$from->toDateString()} → {$to->toDateString()}"),
                ]);
            }
        });
    }

    /**
     * 按课程 assignment_count 均分所有 scheduled/completed 课次，
     * 在每一份尾部往前 remind_before 个课次上打 assignment_reminder=true。
     * 幂等：执行前会先清空所有 assignment_reminder 标记。
     */
    public function markAssignmentReminder(Course $course): void
    {
        $total = (int) $course->assignment_count;
        $before = (int) $course->remind_before;

        DB::transaction(function () use ($course, $total, $before): void {
            $course->sessions()
                ->where('assignment_reminder', true)
                ->update(['assignment_reminder' => false]);

            if ($total <= 0) {
                return;
            }

            $sessions = $course->sessions()
                ->whereIn('status', ['scheduled', 'completed'])
                ->orderBy('seq')
                ->get();

            $count = $sessions->count();
            if ($count === 0) {
                return;
            }

            $interval = max(1, (int) floor($count / $total));

            for ($i = 1; $i <= $total; $i++) {
                $dueIndex = min($i * $interval - 1, $count - 1);
                $remindIndex = max($dueIndex - $before, 0);
                $sessions[$remindIndex]->update(['assignment_reminder' => true]);
            }
        });
    }

    /**
     * 构造课次数据行（不写库，返回数组）。
     *
     * @param  list<string>  $excludeSlots  需要跳过的已占时段，格式 "Y-m-d#period"
     * @return list<array<string, mixed>>
     */
    private function buildSessionRows(Course $course, int $startSeq, array $excludeSlots = []): array
    {
        $weeklyDays = array_map('intval', (array) $course->weekly_days);
        $periods = (array) $course->periods_per_day;
        if ($weeklyDays === [] || $periods === []) {
            return [];
        }

        $holidayDates = Holiday::whereBetween('date', [
            $course->semester_start->copy()->startOfDay(),
            $course->semester_end->copy()->endOfDay(),
        ])
            ->where('type', Holiday::TYPE_HOLIDAY)
            ->pluck('date')
            ->map(fn ($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d)
            ->flip()
            ->all();

        $excludeSet = array_flip($excludeSlots);
        $rows = [];
        $seq = $startSeq;
        $now = now();

        $period = CarbonPeriod::create(
            CarbonImmutable::parse($course->semester_start)->startOfDay(),
            CarbonImmutable::parse($course->semester_end)->startOfDay(),
        );

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            if (isset($holidayDates[$dateStr])) {
                continue;
            }
            $weekday = $date->dayOfWeekIso;
            if (! in_array($weekday, $weeklyDays, true)) {
                continue;
            }
            foreach ($periods as $idx => $_periodCfg) {
                $periodNo = $idx + 1;
                if (isset($excludeSet["{$dateStr}#{$periodNo}"])) {
                    continue;
                }
                $rows[] = [
                    'course_id' => $course->id,
                    'seq' => $seq++,
                    'session_date' => $dateStr,
                    'weekday' => $weekday,
                    'period' => $periodNo,
                    'status' => 'scheduled',
                    'assignment_reminder' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }
}
