<?php

namespace App\Services;

use App\Models\Course;

/**
 * 学情报告生成（前端可视化截图 + Puppeteer 转 PDF）
 */
class ReportService
{
    public function renderHtml(Course $course, array $options = []): string
    {
        throw new \LogicException('Not implemented.');
    }

    public function generatePdf(Course $course, array $options = []): string
    {
        throw new \LogicException('Not implemented.');
    }
}
