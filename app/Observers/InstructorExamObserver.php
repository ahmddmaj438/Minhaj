<?php

namespace App\Observers;

use App\Models\Exam\InstructorExam;
use App\Services\TCExam\TCExamBuilderSync;

class InstructorExamObserver
{
    public function saved(InstructorExam $exam): void
    {
        app(TCExamBuilderSync::class)->syncExam($exam);
    }

    public function deleted(InstructorExam $exam): void
    {
        app(TCExamBuilderSync::class)->deleteExam($exam);
    }
}
