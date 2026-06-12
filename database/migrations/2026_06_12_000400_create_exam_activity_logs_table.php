<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('event', 50);
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['exam_session_id', 'event']);
            $table->index(['student_profile_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_activity_logs');
    }
};
