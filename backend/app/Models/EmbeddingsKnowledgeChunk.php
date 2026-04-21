<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmbeddingsKnowledgeChunk extends Model
{
    use HasFactory;

    protected $table = 'embeddings_knowledge_chunks';

    protected $guarded = ['id'];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function lessonPlanSection(): BelongsTo
    {
        return $this->belongsTo(LessonPlanSection::class);
    }
}
