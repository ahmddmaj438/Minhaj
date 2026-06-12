<?php

namespace App\Observers;

use App\Models\Exam\InstructorExamQuestion;
use App\Services\TCExam\TCExamBuilderSync;

class InstructorExamQuestionObserver
{
    public function saved(InstructorExamQuestion $question): void
    {
        app(TCExamBuilderSync::class)->syncQuestion($question);
    }

    public function deleted(InstructorExamQuestion $question): void
    {
        app(TCExamBuilderSync::class)->deleteQuestion($question);
    }
}
