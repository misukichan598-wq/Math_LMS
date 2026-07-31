<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_of_fame', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rank');
            $table->decimal('final_score', 5, 2);
            $table->decimal('activity_accuracy', 5, 2)->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->integer('total_learning_time')->default(0)->comment('in seconds');
            $table->decimal('improvement_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('rank');
            $table->index('final_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_of_fame');
    }
};
