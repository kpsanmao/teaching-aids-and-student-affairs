<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained('lesson_plans')->cascadeOnDelete();
            $table->unsignedInteger('seq');
            $table->string('title', 500);
            $table->text('summary')->nullable();
            $table->json('knowledge_tags');
            $table->unsignedInteger('suggested_session_start')->nullable();
            $table->unsignedInteger('suggested_session_end')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->timestamps();
            $table->index(['lesson_plan_id', 'seq'], 'idx_sections_plan_seq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_sections');
    }
};
