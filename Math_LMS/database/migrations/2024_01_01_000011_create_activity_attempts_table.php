<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_question_id')->constrained()->onDelete('cascade');
            $table->text('student_answer');
            $table->boolean('is_correct')->default(false);
            $table->integer('points_earned')->default(0);
            $table->timestamp('answered_at');
            $table->timestamps();
            
            $table->index(['user_id', 'activity_id']);
            $table->index('is_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_attempts');
    }
};
