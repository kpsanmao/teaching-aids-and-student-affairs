<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * 课次生成 / 节假日剔除 / 调班 / 作业提醒标记
 */
class ScheduleService
{
    public function generate(Course $course): Collection
    {
        throw new \LogicException('Not implemented.');
    }

    public function regenerate(Course $course): Collection
    {
        throw new \LogicException('Not implemented.');
    }

    public function shift(Course $course, string $fromDate, string $toDate): void
    {
        throw new \LogicException('Not implemented.');
    }

    public function markAssignmentReminder(Course $course): void
    {
        throw new \LogicException('Not implemented.');
    }
}
