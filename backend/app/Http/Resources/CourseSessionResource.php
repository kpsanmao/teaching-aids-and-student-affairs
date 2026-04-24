<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CourseSession
 */
class CourseSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'seq' => $this->seq,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'weekday' => $this->weekday,
            'period' => $this->period,
            'status' => $this->status,
            'assignment_reminder' => (bool) $this->assignment_reminder,
            'topic' => $this->topic,
            'remark' => $this->remark,
            'lesson_plan_section_id' => $this->lesson_plan_section_id,
        ];
    }
}
