<?php

namespace App\Observers;

use App\Models\Exam\InstructorExam;
use App\Services\TCExam\TCExamBuilderSync;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstructorExamObserver
{
    public function saved(InstructorExam $exam): void
    {
        try {
            app(TCExamBuilderSync::class)->syncExam($exam);
        } catch (Throwable $exception) {
            Log::warning('TCExam exam sync failed.', [
                'exam_id' => $exam->id,
                'exception' => $exception::class,
            ]);
        }
    }

    public function deleted(InstructorExam $exam): void
    {
        try {
            app(TCExamBuilderSync::class)->deleteExam($exam);
        } catch (Throwable $exception) {
            Log::warning('TCExam exam delete sync failed.', [
                'exam_id' => $exam->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
