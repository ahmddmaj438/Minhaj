<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->string('status')->default('assigned');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index(['student_profile_id', 'status']);
            $table->index(['instructor_exam_id', 'course_id']);
            $table->unique(['instructor_exam_id', 'course_id', 'student_profile_id'], 'exam_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_assignments');
    }
};
