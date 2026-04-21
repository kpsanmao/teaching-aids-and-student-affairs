<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->unsignedInteger('weight')->default(0);
            $table->date('published_at')->nullable();
            $table->date('due_at')->nullable();
            $table->enum('status', ['draft', 'published', 'grading', 'finished'])->default('draft');
            $table->timestamps();
            $table->index(['course_id', 'status'], 'idx_assignments_course_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
