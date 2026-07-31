<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('pdf_path')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('estimated_duration')->nullable()->comment('in minutes');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
