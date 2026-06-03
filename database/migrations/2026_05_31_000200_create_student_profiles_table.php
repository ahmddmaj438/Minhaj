<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('academic_status')->default('active');
            $table->unsignedSmallInteger('admission_year')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['major_id', 'academic_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
