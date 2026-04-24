<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_no' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('students', 'student_no')->ignore($studentId),
            ],
            'name' => ['sometimes', 'string', 'max:100'],
            'class_name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
