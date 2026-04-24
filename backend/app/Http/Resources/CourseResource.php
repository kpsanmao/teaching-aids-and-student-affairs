<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Course
 */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'credit' => (float) $this->credit,
            'course_type' => $this->course_type,
            'semester' => $this->semester,
            'semester_start' => $this->semester_start?->format('Y-m-d'),
            'semester_end' => $this->semester_end?->format('Y-m-d'),
            'weekly_days' => $this->weekly_days,
            'periods_per_day' => $this->periods_per_day,
            'assignment_count' => $this->assignment_count,
            'max_absence' => $this->max_absence,
            'remind_before' => $this->remind_before,
            'grade_formula' => $this->grade_formula,
            'alert_thresholds' => $this->alert_thresholds,
            'teacher' => UserResource::make($this->whenLoaded('teacher')),
            'sessions_count' => $this->when(isset($this->sessions_count), $this->sessions_count),
            'students_count' => $this->when(isset($this->students_count), $this->students_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
