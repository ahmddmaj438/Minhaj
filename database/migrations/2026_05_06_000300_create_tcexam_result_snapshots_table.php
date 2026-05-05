<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcexam_result_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tcexam_test_link_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('tcexam_test_id');
            $table->unsignedBigInteger('tcexam_testuser_id')->nullable();
            $table->unsignedBigInteger('tcexam_result_id')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tcexam_test_id', 'tcexam_testuser_id', 'tcexam_result_id'], 'tcexam_result_unique');
            $table->index(['user_id', 'tcexam_test_id']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcexam_result_snapshots');
    }
};
