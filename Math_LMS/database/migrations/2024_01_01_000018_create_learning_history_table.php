<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // lesson_start, lesson_complete, section_complete, activity_complete, assessment_start, assessment_complete
            $table->string('activity_description');
            $table->morphs('related'); // Can be lesson, section, activity, or assessment
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('activity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_history');
    }
};
