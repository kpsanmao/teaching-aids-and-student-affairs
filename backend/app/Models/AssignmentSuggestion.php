<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSuggestion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'knowledge_tags' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }

    public function adoptedAssignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'adopted_assignment_id');
    }
}
