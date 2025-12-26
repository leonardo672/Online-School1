<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            // Relation
            $table->foreignId('course_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Lesson content
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();

            // Order inside course
            $table->unsignedInteger('position')->default(1);

            $table->timestamps();

            // Ensure lesson order uniqueness per course
            $table->unique(['course_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
