<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_question_id')->constrained()->onDelete('cascade');
            $table->string('student_answer');
            $table->boolean('is_correct')->default(false);
            $table->integer('points_earned')->default(0);
            $table->timestamps();
            
            $table->index(['assessment_attempt_id', 'assessment_question_id'], 'attempt_question_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
