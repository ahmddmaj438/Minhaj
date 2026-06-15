<?php

namespace App\Observers;

use App\Models\Exam\InstructorExamQuestion;
use App\Services\TCExam\TCExamBuilderSync;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstructorExamQuestionObserver
{
    public function saved(InstructorExamQuestion $question): void
    {
        try {
            app(TCExamBuilderSync::class)->syncQuestion($question);
        } catch (Throwable $exception) {
            Log::warning('TCExam question sync failed.', [
                'exam_id' => $question->instructor_exam_id,
                'question_id' => $question->id,
                'exception' => $exception::class,
            ]);
        }
    }

    public function deleted(InstructorExamQuestion $question): void
    {
        try {
            app(TCExamBuilderSync::class)->deleteQuestion($question);
        } catch (Throwable $exception) {
            Log::warning('TCExam question delete sync failed.', [
                'exam_id' => $question->instructor_exam_id,
                'question_id' => $question->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
