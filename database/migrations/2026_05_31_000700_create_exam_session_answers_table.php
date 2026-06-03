<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_session_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_exam_question_id')->constrained()->cascadeOnDelete();
            $table->json('answer_payload')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_session_id', 'instructor_exam_question_id'], 'exam_session_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_session_answers');
    }
};
