<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_exam_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('category');
            $table->string('title')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->decimal('marks', 8, 2)->default(1);
            $table->string('difficulty')->nullable();
            $table->string('topic')->nullable();
            $table->string('programming_language')->nullable();
            $table->boolean('save_to_bank')->default(false);
            $table->json('prompt')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['instructor_exam_id', 'position']);
            $table->index(['type', 'category']);
            $table->index(['difficulty', 'topic']);
            $table->index('programming_language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_exam_questions');
    }
};
