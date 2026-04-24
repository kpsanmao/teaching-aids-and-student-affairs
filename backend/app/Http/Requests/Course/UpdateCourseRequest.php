<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course && ($this->user()?->can('update', $course) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'credit' => ['sometimes', 'numeric', 'between:0.5,10'],
            'course_type' => ['sometimes', Rule::in(['theory', 'practice', 'mixed'])],
            'semester' => ['sometimes', 'string', 'max:20'],
            'semester_start' => ['sometimes', 'date'],
            'semester_end' => ['sometimes', 'date', 'after:semester_start'],
            'weekly_days' => ['sometimes', 'array', 'min:1'],
            'weekly_days.*' => ['integer', 'between:1,7'],
            'periods_per_day' => ['sometimes', 'array', 'min:1'],
            'periods_per_day.*' => ['array'],
            'assignment_count' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'max_absence' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'remind_before' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'grade_formula' => ['sometimes', 'array'],
            'alert_thresholds' => ['sometimes', 'array'],
        ];
    }
}
