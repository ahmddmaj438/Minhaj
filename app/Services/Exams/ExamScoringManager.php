<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Services\Exams\Grading\SessionGradeCalculator;

class ExamScoringManager
{
    public function __construct(
        private readonly SessionGradeCalculator $gradeCalculator,
    ) {}

    public function scoreSubmittedSession(ExamSession $session): ExamSession
    {
        $session->loadMissing('assignment.exam.questions', 'answers.question');

        foreach ($session->assignment->exam->questions as $question) {
            $answer = $session->answers->firstWhere('instructor_exam_question_id', $question->id)
                ?? ExamSessionAnswer::firstOrCreate([
                    'exam_session_id' => $session->id,
                    'instructor_exam_question_id' => $question->id,
                ]);

            if ($this->requiresManualGrading($question)) {
                $this->markManualPending($answer);
                continue;
            }

            $this->autoGrade($answer, $question);
        }

        return $this->recomputeSession($session);
    }

    public function recomputeSession(ExamSession $session): ExamSession
    {
        $session->loadMissing('assignment.exam.questions', 'answers.question');
        $grade = $this->gradeCalculator->calculate($session, fn (?InstructorExamQuestion $question): bool => $this->requiresManualGrading($question));

        $metadata = $session->metadata ?? [];
        $metadata['manual_grading_pending'] = $grade->hasManualPending();
        $metadata['manual_grading_pending_count'] = $grade->manualPendingCount;
        $metadata['last_scored_at'] = now()->toISOString();
        $metadata['grading'] = [
            'source' => 'question_marks',
            'earned_question_marks' => $grade->earnedMarks,
            'possible_question_marks' => $grade->possibleMarks,
            'score_out_of_100' => $grade->scoreOutOf100,
            'question_count' => $grade->questions->count(),
        ];

        $session->update([
            'score' => $grade->earnedMarks,
            'max_score' => $grade->possibleMarks,
            'percentage' => $grade->scoreOutOf100,
            'passed' => $grade->scoreOutOf100 !== null ? $grade->scoreOutOf100 >= 50 : null,
            'metadata' => $metadata,
        ]);

        return $session->refresh();
    }

    public function requiresManualGrading(?InstructorExamQuestion $question): bool
    {
        if (! $question instanceof InstructorExamQuestion) {
            return true;
        }

        return $question->type === 'essay'
            || $question->type === 'packet_tracer'
            || $question->category === 'coding';
    }

    private function autoGrade(ExamSessionAnswer $answer, InstructorExamQuestion $question): void
    {
        [$score, $feedback] = match ($question->type) {
            'mcq' => $this->scoreMcq($answer, $question),
            'true_false', 'true_false_correct' => $this->scoreTrueFalse($answer, $question),
            'matching' => $this->scoreMatching($answer, $question),
            'fill_blank' => $this->scoreFillBlank($answer, $question),
            default => [0.0, 'No automatic scoring rule is configured for this question type.'],
        };

        $payload = $answer->answer_payload ?? [];
        $payload['status'] = 'auto_graded';
        $payload['graded_at'] = now()->toISOString();

        $answer->update([
            'score' => min(max($score, 0), (float) $question->marks),
            'feedback' => $feedback,
            'answer_payload' => $payload,
        ]);
    }

    private function markManualPending(ExamSessionAnswer $answer): void
    {
        if ($answer->score !== null) {
            return;
        }

        $payload = $answer->answer_payload ?? [];
        $payload['status'] = 'manual_pending';

        $answer->update([
            'feedback' => $answer->feedback ?? 'Pending instructor grading.',
            'answer_payload' => $payload,
        ]);
    }

    private function scoreMcq(ExamSessionAnswer $answer, InstructorExamQuestion $question): array
    {
        $selected = collect($answer->answer_payload['value']['selected_options'] ?? [])
            ->map(fn ($value): string => (string) $value)
            ->sort()
            ->values();
        $correct = collect($question->settings['options'] ?? [])
            ->filter(fn (array $option): bool => (bool) ($option['is_correct'] ?? false))
            ->map(fn (array $option, int $index): string => (string) ($option['key'] ?? 'option_'.($index + 1)))
            ->sort()
            ->values();

        if ($correct->isEmpty()) {
            return [0.0, 'No correct option is configured.'];
        }

        return [$selected->all() === $correct->all() ? (float) $question->marks : 0.0, 'Automatically graded.'];
    }

    private function scoreTrueFalse(ExamSessionAnswer $answer, InstructorExamQuestion $question): array
    {
        $submitted = $answer->answer_payload['value']['answer'] ?? null;
        $expected = (bool) ($question->settings['correct_answer'] ?? false) ? 'true' : 'false';

        return [$submitted === $expected ? (float) $question->marks : 0.0, 'Automatically graded.'];
    }

    private function scoreMatching(ExamSessionAnswer $answer, InstructorExamQuestion $question): array
    {
        $pairs = collect($question->settings['pairs'] ?? []);
        if ($pairs->isEmpty()) {
            return [0.0, 'No matching pairs are configured.'];
        }

        $submitted = collect($answer->answer_payload['value']['matches'] ?? []);
        $correct = $pairs->filter(function (array $pair, int $index) use ($submitted): bool {
            return $this->normalizeText($submitted->get($index)) === $this->normalizeText($pair['right'] ?? '');
        })->count();

        return [round(((float) $question->marks / $pairs->count()) * $correct, 2), 'Automatically graded by matching pair.'];
    }

    private function scoreFillBlank(ExamSessionAnswer $answer, InstructorExamQuestion $question): array
    {
        $blanks = collect($question->settings['blanks'] ?? []);
        if ($blanks->isEmpty()) {
            return [0.0, 'No blanks are configured.'];
        }

        $submitted = collect($answer->answer_payload['value']['blanks'] ?? []);
        $caseSensitive = (bool) ($question->settings['case_sensitive'] ?? false);
        $trimWhitespace = (bool) ($question->settings['trim_whitespace'] ?? true);

        $correct = $blanks->filter(function (array $blank, int $index) use ($submitted, $caseSensitive, $trimWhitespace): bool {
            $value = $this->normalizeText($submitted->get($index), $caseSensitive, $trimWhitespace);

            return collect($blank['accepted_answers'] ?? [])
                ->contains(fn ($accepted): bool => $value === $this->normalizeText($accepted, $caseSensitive, $trimWhitespace));
        })->count();

        return [round(((float) $question->marks / $blanks->count()) * $correct, 2), 'Automatically graded by accepted blank answers.'];
    }

    private function normalizeText(mixed $value, bool $caseSensitive = false, bool $trimWhitespace = true): string
    {
        $text = (string) ($value ?? '');
        $text = $trimWhitespace ? trim($text) : $text;

        return $caseSensitive ? $text : mb_strtolower($text);
    }
}
