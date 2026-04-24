<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'student_no' => ['required', 'string', 'max:30', 'unique:students,student_no'],
            'name' => ['required', 'string', 'max:100'],
            'class_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_no' => '学号',
            'name' => '姓名',
            'class_name' => '班级',
        ];
    }
}
