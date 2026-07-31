<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['initial', 'final']);
            $table->integer('time_limit')->nullable()->comment('in minutes');
            $table->integer('passing_score')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
