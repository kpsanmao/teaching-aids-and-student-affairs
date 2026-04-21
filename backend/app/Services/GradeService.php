<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;

/**
 * 成绩公式引擎 / 总评预测
 */
class GradeService
{
    public function evaluate(Course $course, Student $student): array
    {
        throw new \LogicException('Not implemented.');
    }

    public function predict(Course $course, Student $student): float
    {
        throw new \LogicException('Not implemented.');
    }

    public function aggregateCourse(Course $course): array
    {
        throw new \LogicException('Not implemented.');
    }
}
