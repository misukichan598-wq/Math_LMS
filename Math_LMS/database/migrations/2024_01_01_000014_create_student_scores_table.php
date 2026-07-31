<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('score_type', ['initial_assessment', 'final_assessment', 'activity', 'overall']);
            $table->decimal('score', 5, 2);
            $table->integer('max_score');
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            
            $table->index(['user_id', 'score_type']);
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scores');
    }
};
