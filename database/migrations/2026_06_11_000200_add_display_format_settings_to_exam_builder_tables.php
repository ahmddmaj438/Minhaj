<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_exams', function (Blueprint $table) {
            if (! Schema::hasColumn('instructor_exams', 'display_format')) {
                $table->string('display_format', 40)
                    ->default('one_question_at_time')
                    ->after('total_marks')
                    ->index();
            }
        });

        Schema::table('instructor_exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('instructor_exam_questions', 'display_override')) {
                $table->string('display_override', 40)
                    ->default('standard')
                    ->after('programming_language')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('instructor_exam_questions', function (Blueprint $table) {
            if (Schema::hasColumn('instructor_exam_questions', 'display_override')) {
                $table->dropColumn('display_override');
            }
        });

        Schema::table('instructor_exams', function (Blueprint $table) {
            if (Schema::hasColumn('instructor_exams', 'display_format')) {
                $table->dropColumn('display_format');
            }
        });
    }
};
