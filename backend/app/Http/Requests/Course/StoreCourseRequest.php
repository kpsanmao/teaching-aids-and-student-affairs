<?php

namespace App\Http\Requests\Course;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'credit' => ['required', 'numeric', 'between:0.5,10'],
            'course_type' => ['required', Rule::in(['theory', 'practice', 'mixed'])],
            'semester' => ['required', 'string', 'max:20'],
            'semester_start' => ['required', 'date'],
            'semester_end' => ['required', 'date', 'after:semester_start'],
            'weekly_days' => ['required', 'array', 'min:1'],
            'weekly_days.*' => ['integer', 'between:1,7'],
            'periods_per_day' => ['required', 'array', 'min:1'],
            'periods_per_day.*' => ['array'],
            'assignment_count' => ['required', 'integer', 'min:0', 'max:20'],
            'max_absence' => ['required', 'integer', 'min:0', 'max:50'],
            'remind_before' => ['required', 'integer', 'min:0', 'max:10'],
            'grade_formula' => ['required', 'array'],
            'alert_thresholds' => ['required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '课程名称',
            'credit' => '学分',
            'course_type' => '课程类型',
            'semester' => '学期',
            'semester_start' => '学期开始日期',
            'semester_end' => '学期结束日期',
            'weekly_days' => '上课星期',
            'periods_per_day' => '每日节次',
            'assignment_count' => '作业次数',
            'max_absence' => '旷课上限',
            'remind_before' => '作业提前提醒课次数',
            'grade_formula' => '成绩公式',
            'alert_thresholds' => '预警阈值',
        ];
    }
}
