<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_no', 30)->unique();
            $table->string('name', 100);
            $table->string('class_name', 100);
            $table->timestamps();
            $table->index('class_name', 'idx_students_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
