<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeddings_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_section_id')
                ->nullable()
                ->constrained('lesson_plan_sections')
                ->nullOnDelete();
            $table->text('content');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('lesson_plan_section_id', 'idx_embeddings_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings_knowledge_chunks');
    }
};
