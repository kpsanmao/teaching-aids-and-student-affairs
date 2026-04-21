<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['absence', 'grade_low', 'grade_decline', 'missing_assignment']);
            $table->enum('level', ['warning', 'critical'])->default('warning');
            $table->string('title', 200);
            $table->text('content');
            $table->json('context')->nullable();
            $table->enum('status', ['active', 'resolved', 'dismissed'])->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['course_id', 'status'], 'idx_alerts_course_status');
            $table->index(['student_id', 'type'], 'idx_alerts_student_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
