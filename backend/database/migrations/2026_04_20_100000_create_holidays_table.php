<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 100);
            $table->enum('type', ['holiday', 'workday']);
            $table->year('year');
            $table->timestamps();
            $table->index('year', 'idx_holidays_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
