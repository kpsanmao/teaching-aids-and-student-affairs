<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedInteger('seq');
            $table->date('session_date');
            $table->unsignedInteger('weekday');
            $table->unsignedInteger('period');
            $table->foreignId('lesson_plan_section_id')
                ->nullable()
                ->constrained('lesson_plan_sections')
                ->nullOnDelete();
            $table->string('topic', 500)->nullable();
            $table->enum('status', ['scheduled', 'completed', 'skipped', 'cancelled'])->default('scheduled');
            $table->boolean('assignment_reminder')->default(false);
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'seq'], 'uq_session_course_seq');
            $table->index('session_date', 'idx_sessions_date');
            $table->index(['course_id', 'session_date'], 'idx_sessions_course_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
