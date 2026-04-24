<?php

namespace App\Http\Requests\Student;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course
            && ($this->user()?->can('update', $course) ?? false);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                // Excel / CSV；文件大小限制 5MB
                'mimes:xlsx,xls,csv,txt',
                'max:5120',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => '学生名单文件',
        ];
    }
}
