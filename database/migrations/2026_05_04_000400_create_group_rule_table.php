<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_rule', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained()->cascadeOnDelete();
            $table->primary(['group_id', 'rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_rule');
    }
};

