<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Exam\InstructorExam;
use App\Services\TCExam\TCExamBuilderSync;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_exams', function (Blueprint $table) {
            if (! Schema::hasColumn('instructor_exams', 'tcexam_test_id')) {
                $table->unsignedBigInteger('tcexam_test_id')->nullable()->after('published_at')->index();
            }
        });

        Schema::table('instructor_exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('instructor_exam_questions', 'tcexam_question_id')) {
                $table->unsignedBigInteger('tcexam_question_id')->nullable()->after('save_to_bank')->index();
            }

            if (! Schema::hasColumn('instructor_exam_questions', 'tcexam_subject_id')) {
                $table->unsignedBigInteger('tcexam_subject_id')->nullable()->after('tcexam_question_id')->index();
            }
        });

        $sync = app(TCExamBuilderSync::class);

        InstructorExam::with('questions')->chunkById(50, function ($exams) use ($sync): void {
            foreach ($exams as $exam) {
                $sync->syncExam($exam);

                foreach ($exam->questions as $question) {
                    $sync->syncQuestion($question);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('instructor_exam_questions', function (Blueprint $table) {
            if (Schema::hasColumn('instructor_exam_questions', 'tcexam_subject_id')) {
                $table->dropColumn('tcexam_subject_id');
            }

            if (Schema::hasColumn('instructor_exam_questions', 'tcexam_question_id')) {
                $table->dropColumn('tcexam_question_id');
            }
        });

        Schema::table('instructor_exams', function (Blueprint $table) {
            if (Schema::hasColumn('instructor_exams', 'tcexam_test_id')) {
                $table->dropColumn('tcexam_test_id');
            }
        });
    }
};
