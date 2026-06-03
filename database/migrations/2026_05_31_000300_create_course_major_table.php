<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_major', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('recommended_level')->nullable();
            $table->timestamps();

            $table->primary(['course_id', 'major_id']);
            $table->index(['major_id', 'recommended_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_major');
    }
};
