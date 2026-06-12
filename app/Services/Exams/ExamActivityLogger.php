<?php

namespace App\Services\Exams;

use App\Models\ExamActivityLog;
use App\Models\ExamSession;

class ExamActivityLogger
{
    public function record(ExamSession $session, string $event, array $context = []): ExamActivityLog
    {
        return ExamActivityLog::create([
            'exam_session_id' => $session->id,
            'student_profile_id' => $session->student_profile_id,
            'event' => $event,
            'context' => $context ?: null,
            'occurred_at' => now(),
        ]);
    }
}
