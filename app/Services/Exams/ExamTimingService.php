<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use Carbon\CarbonInterface;

class ExamTimingService
{
    public function sessionExpiresAt(ExamAssignment $assignment, CarbonInterface $startedAt): ?CarbonInterface
    {
        $exam = $assignment->relationLoaded('exam')
            ? $assignment->exam
            : $assignment->exam()->first();

        $durationMinutes = (int) ($exam?->duration_minutes ?? 0);
        $durationExpiry = $durationMinutes > 0
            ? $startedAt->copy()->addMinutes($durationMinutes)
            : null;

        if ($durationExpiry && $assignment->due_at) {
            return $durationExpiry->lte($assignment->due_at) ? $durationExpiry : $assignment->due_at;
        }

        return $durationExpiry ?? $assignment->due_at;
    }

    public function questionTimingPlan(InstructorExam $exam): array
    {
        $questions = $exam->relationLoaded('questions')
            ? $exam->questions
            : $exam->questions()->get();

        $durationSeconds = (int) $exam->duration_minutes * 60;
        if ($durationSeconds <= 0 || $questions->isEmpty()) {
            return [];
        }

        $totalMarks = $questions->sum(fn (InstructorExamQuestion $question): float => max((float) $question->marks, 0));
        $equalSeconds = (int) floor($durationSeconds / max($questions->count(), 1));

        return $questions
            ->mapWithKeys(function (InstructorExamQuestion $question) use ($durationSeconds, $equalSeconds, $totalMarks): array {
                $explicitSeconds = $this->explicitQuestionSeconds($question);
                $seconds = $explicitSeconds;
                $source = 'explicit';

                if ($seconds === null && $totalMarks > 0) {
                    $seconds = (int) floor($durationSeconds * ((float) $question->marks / $totalMarks));
                    $source = 'marks';
                }

                if ($seconds === null || $seconds <= 0) {
                    $seconds = max($equalSeconds, 60);
                    $source = 'equal';
                }

                return [
                    $question->id => [
                        'seconds' => $seconds,
                        'label' => $this->formatSeconds($seconds),
                        'source' => $source,
                    ],
                ];
            })
            ->all();
    }

    private function explicitQuestionSeconds(InstructorExamQuestion $question): ?int
    {
        $settings = $question->settings ?? [];
        $seconds = $settings['time_limit_seconds'] ?? $settings['question_time_seconds'] ?? null;
        $minutes = $settings['time_limit_minutes'] ?? $settings['question_time_minutes'] ?? null;

        if (is_numeric($seconds) && (int) $seconds > 0) {
            return (int) $seconds;
        }

        if (is_numeric($minutes) && (int) $minutes > 0) {
            return (int) $minutes * 60;
        }

        return null;
    }

    private function formatSeconds(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0 && $remainingSeconds > 0) {
            return $minutes.'m '.$remainingSeconds.'s';
        }

        if ($minutes > 0) {
            return $minutes.'m';
        }

        return $seconds.'s';
    }
}
