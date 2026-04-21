<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 300);
            $table->string('file_path', 500);
            $table->enum('file_type', ['docx'])->default('docx');
            $table->unsignedInteger('file_size');
            $table->char('file_hash', 64)->nullable();
            $table->enum('status', ['pending', 'analyzing', 'completed', 'failed'])->default('pending');
            $table->json('ai_raw_response')->nullable();
            $table->unsignedBigInteger('ai_conversation_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index('course_id', 'idx_lesson_plans_course');
            $table->index('status', 'idx_lesson_plans_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
