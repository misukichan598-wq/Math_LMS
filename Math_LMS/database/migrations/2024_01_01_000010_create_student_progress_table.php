<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_section_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent')->default(0)->comment('in seconds');
            $table->timestamps();
            
            $table->unique(['user_id', 'lesson_id', 'lesson_section_id']);
            $table->index(['user_id', 'lesson_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
