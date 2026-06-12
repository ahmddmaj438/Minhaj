<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->unique();
            $table->text('api_key')->nullable();
            $table->string('model_name', 150);
            $table->string('base_url', 500)->nullable();
            $table->string('status', 20)->default('inactive')->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
