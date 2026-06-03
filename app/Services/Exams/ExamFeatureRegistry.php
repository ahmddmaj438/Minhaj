<?php

namespace App\Services\Exams;

use App\Models\ExamAssignment;
use App\Models\ExamSession;

class ExamFeatureRegistry
{
    public function assignmentSettings(): array
    {
        return [
            'show_score_to_student' => [
                'label' => 'Show score to student',
                'description' => 'Students can see the final score after automatic and manual grading is complete.',
                'default' => false,
            ],
            'show_feedback_to_student' => [
                'label' => 'Show feedback to student',
                'description' => 'Students can see instructor feedback after grading.',
                'default' => false,
            ],
        ];
    }

    public function defaults(): array
    {
        return collect($this->assignmentSettings())
            ->mapWithKeys(fn (array $feature, string $key): array => [$key => $feature['default']])
            ->all();
    }

    public function showScoreToStudent(ExamAssignment $assignment, ?ExamSession $session = null): bool
    {
        if (! (bool) (($assignment->settings ?? [])['show_score_to_student'] ?? false)) {
            return false;
        }

        if (! $session || $session->status !== ExamSession::STATUS_SUBMITTED) {
            return false;
        }

        return empty(($session->metadata ?? [])['manual_grading_pending']);
    }

    public function showFeedbackToStudent(ExamAssignment $assignment, ?ExamSession $session = null): bool
    {
        if (! (bool) (($assignment->settings ?? [])['show_feedback_to_student'] ?? false)) {
            return false;
        }

        return $this->showScoreToStudent($assignment, $session);
    }
}
