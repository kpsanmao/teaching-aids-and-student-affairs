<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Course;

/**
 * 预警规则引擎
 */
class AlertService
{
    public function checkAbsence(Course $course): void
    {
        throw new \LogicException('Not implemented.');
    }

    public function checkGradeLow(Course $course): void
    {
        throw new \LogicException('Not implemented.');
    }

    public function checkGradeDecline(Course $course): void
    {
        throw new \LogicException('Not implemented.');
    }

    public function checkMissingAssignment(Course $course): void
    {
        throw new \LogicException('Not implemented.');
    }

    public function resolve(Alert $alert, int $userId, ?string $note = null): Alert
    {
        throw new \LogicException('Not implemented.');
    }
}
