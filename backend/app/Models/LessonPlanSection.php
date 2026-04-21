<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonPlanSection extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'knowledge_tags' => 'array',
        'confirmed' => 'boolean',
    ];

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }

    public function embeddingChunks(): HasMany
    {
        return $this->hasMany(EmbeddingsKnowledgeChunk::class);
    }
}
