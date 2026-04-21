<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_session_id')->nullable()->constrained('course_sessions')->nullOnDelete();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->json('knowledge_tags');
            $table->unsignedBigInteger('ai_conversation_id')->nullable();
            $table->enum('status', ['pending', 'adopted', 'rejected'])->default('pending');
            $table->foreignId('adopted_assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->timestamps();
            $table->index(['course_id', 'status'], 'idx_suggestions_course_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_suggestions');
    }
};
