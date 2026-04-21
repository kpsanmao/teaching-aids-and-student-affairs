<?php

namespace App\Services;

use App\Models\Course;

/**
 * 学情分析与可视化数据聚合
 */
class AnalyticsService
{
    public function courseOverview(Course $course): array
    {
        throw new \LogicException('Not implemented.');
    }

    public function scoreDistribution(Course $course): array
    {
        throw new \LogicException('Not implemented.');
    }

    public function attendanceHeatmap(Course $course): array
    {
        throw new \LogicException('Not implemented.');
    }

    public function trend(Course $course): array
    {
        throw new \LogicException('Not implemented.');
    }
}
