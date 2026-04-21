<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 200);
            $table->decimal('credit', 3, 1)->default(2.0);
            $table->enum('course_type', ['theory', 'practice', 'mixed'])->default('mixed');
            $table->string('semester', 20);
            $table->date('semester_start');
            $table->date('semester_end');
            $table->json('weekly_days');
            $table->json('periods_per_day');
            $table->unsignedInteger('assignment_count')->default(4);
            $table->unsignedInteger('max_absence')->default(6);
            $table->unsignedInteger('remind_before')->default(1);
            $table->json('grade_formula');
            $table->json('alert_thresholds');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'semester'], 'idx_courses_user_semester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
