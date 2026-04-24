<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Student
 */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_no' => $this->student_no,
            'name' => $this->name,
            'class_name' => $this->class_name,
            'courses_count' => $this->when(isset($this->courses_count), $this->courses_count),
            'enrolled_at' => $this->whenPivotLoaded('course_student', fn () => $this->pivot->enrolled_at),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
