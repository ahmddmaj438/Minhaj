<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_student', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('enrollment_status')->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->primary(['course_id', 'student_profile_id']);
            $table->index(['student_profile_id', 'enrollment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};
