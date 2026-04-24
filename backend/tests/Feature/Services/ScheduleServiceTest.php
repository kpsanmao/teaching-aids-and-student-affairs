<?php

namespace Tests\Feature\Services;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Holiday;
use App\Models\Student;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    /** 场景：2026-03-02（周一）~ 2026-03-29（周日），共 4 周；周一 + 周三；每天 2 节 */
    private function makeCourse(array $overrides = []): Course
    {
        return Course::factory()->create(array_merge([
            'semester_start' => '2026-03-02',
            'semester_end' => '2026-03-29',
            'weekly_days' => [1, 3],
            'periods_per_day' => [
                ['start' => '08:00', 'end' => '09:40'],
                ['start' => '10:00', 'end' => '11:40'],
            ],
            'assignment_count' => 2,
            'remind_before' => 1,
        ], $overrides));
    }

    public function test_generate_creates_expected_sessions_by_weekly_days(): void
    {
        $course = $this->makeCourse();

        $sessions = app(ScheduleService::class)->generate($course);

        // 4 周 × 2 天 × 2 节 = 16 节课次
        $this->assertCount(16, $sessions);
        $this->assertSame(1, $sessions->first()->seq);
        $this->assertSame(16, $sessions->last()->seq);
        $this->assertTrue($sessions->every(fn ($s) => in_array($s->weekday, [1, 3], true)));
        $this->assertSame('2026-03-02', $sessions->first()->session_date->format('Y-m-d'));
    }

    public function test_generate_skips_legal_holiday(): void
    {
        Holiday::factory()->create([
            'date' => '2026-03-09', // 周一
            'name' => '测试假日',
            'type' => Holiday::TYPE_HOLIDAY,
            'year' => 2026,
        ]);

        $course = $this->makeCourse();
        $sessions = app(ScheduleService::class)->generate($course);

        // 被跳掉 2 节（周一两个时段）
        $this->assertCount(14, $sessions);
        $this->assertFalse(
            $sessions->contains(fn ($s) => $s->session_date->format('Y-m-d') === '2026-03-09')
        );
    }

    public function test_generate_honors_workday_non_holiday(): void
    {
        // 2026-03-08 是周日：加一条 workday 后，weekly_days 包含 7 时应该正常排课
        Holiday::factory()->create([
            'date' => '2026-03-08',
            'name' => '调休补班',
            'type' => Holiday::TYPE_WORKDAY,
            'year' => 2026,
        ]);

        $course = $this->makeCourse(['weekly_days' => [7]]); // 仅周日
        $sessions = app(ScheduleService::class)->generate($course);

        // 4 个周日 × 2 节 = 8 节（包含 03-08 那天，因为 workday 不被跳过）
        $this->assertCount(8, $sessions);
        $this->assertTrue(
            $sessions->contains(fn ($s) => $s->session_date->format('Y-m-d') === '2026-03-08')
        );
    }

    public function test_generate_refuses_when_sessions_exist(): void
    {
        $course = $this->makeCourse();
        app(ScheduleService::class)->generate($course);

        $this->expectException(\DomainException::class);
        app(ScheduleService::class)->generate($course);
    }

    public function test_mark_assignment_reminder_distributes_evenly(): void
    {
        $course = $this->makeCourse(['assignment_count' => 2, 'remind_before' => 1]);
        app(ScheduleService::class)->generate($course);

        $reminders = $course->sessions()
            ->where('assignment_reminder', true)
            ->orderBy('seq')
            ->pluck('seq');

        // 16 节课、2 次作业 → interval=8；due=8、16，减去 remind_before=1 → reminder seq = 7, 15
        $this->assertEquals([7, 15], $reminders->all());
    }

    public function test_regenerate_preserves_completed_sessions(): void
    {
        $course = $this->makeCourse();
        app(ScheduleService::class)->generate($course);

        $completed = $course->sessions()->orderBy('seq')->take(3)->get();
        $completed->each->update(['status' => 'completed']);

        // 人为破坏其中一个未来 scheduled 课次的日期，验证 regenerate 会把它清掉并补回正确分布
        $course->sessions()->where('seq', 10)->update(['session_date' => '2026-03-31']);

        $result = app(ScheduleService::class)->regenerate($course);

        $statuses = $result->groupBy('status')->map->count();
        $this->assertSame(3, $statuses['completed']);
        $this->assertGreaterThanOrEqual(13, $statuses['scheduled']);
        $this->assertFalse(
            $result->contains(fn ($s) => $s->session_date->format('Y-m-d') === '2026-03-31')
        );
    }

    public function test_regenerate_keeps_sessions_with_attendance(): void
    {
        $course = $this->makeCourse();
        app(ScheduleService::class)->generate($course);

        $session = $course->sessions()->first();
        $student = Student::factory()->create();
        Attendance::create([
            'course_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        app(ScheduleService::class)->regenerate($course);

        $this->assertDatabaseHas('course_sessions', ['id' => $session->id]);
    }

    public function test_shift_moves_sessions_to_new_date(): void
    {
        $course = $this->makeCourse();
        app(ScheduleService::class)->generate($course);

        app(ScheduleService::class)->shift($course, '2026-03-02', '2026-03-10');

        $movedCount = $course->sessions()
            ->whereDate('session_date', '2026-03-10')
            ->count();

        // 原 03-02（周一）有 2 节 + 原 03-10（周二）没有课 → 现在应有 2 节
        $this->assertSame(2, $movedCount);
        $this->assertSame(
            0,
            $course->sessions()->whereDate('session_date', '2026-03-02')->count()
        );
    }

    public function test_shift_rejects_target_on_holiday(): void
    {
        Holiday::factory()->create([
            'date' => '2026-03-10',
            'name' => '测试假日',
            'type' => Holiday::TYPE_HOLIDAY,
            'year' => 2026,
        ]);

        $course = $this->makeCourse();
        app(ScheduleService::class)->generate($course);

        $this->expectException(\DomainException::class);
        app(ScheduleService::class)->shift($course, '2026-03-02', '2026-03-10');
    }
}
