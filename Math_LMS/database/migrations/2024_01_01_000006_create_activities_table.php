<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_section_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->enum('type', [
                'multiple_choice',
                'true_false',
                'matching',
                'fill_blank',
                'drag_drop',
                'arrange_order',
                'identify'
            ]);
            $table->integer('order')->default(0);
            $table->integer('passing_score')->default(70);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->index(['lesson_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
