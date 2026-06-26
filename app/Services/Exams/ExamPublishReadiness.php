<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExam;
use App\Support\Exams\ExamDisplayFormatCatalog;

class ExamPublishReadiness
{
    public function inspect(InstructorExam $exam): array
    {
        $questions = $exam->questions()->get();
        $questionMarks = (float) $questions->sum(fn ($question): float => (float) $question->marks);
        $examMarks = (float) $exam->total_marks;
        $configuredQuestions = $questions->filter(
            fn ($question): bool => empty($this->questionIssues($question))
        )->count();

        $checks = [
            [
                'key' => 'information',
                'label' => 'Exam Header',
                'description' => 'Title, course, duration, and total marks are complete.',
                'passed' => filled($exam->title)
                    && $exam->course_id !== null
                    && $exam->duration_minutes >= 5
                    && $examMarks > 0
                    && (! $exam->starts_at || ! $exam->ends_at || $exam->starts_at->lt($exam->ends_at)),
                'action' => route('instructor.exams.edit', $exam),
            ],
            [
                'key' => 'format',
                'label' => 'Instructions',
                'description' => 'Student instructions and rules are ready to review.',
                'passed' => in_array($exam->display_format, ExamDisplayFormatCatalog::keys(), true),
                'action' => route('instructor.exams.edit', $exam).'#exam-instructions',
            ],
            [
                'key' => 'questions',
                'label' => 'Questions',
                'description' => $questions->isEmpty()
                    ? 'Add at least one question.'
                    : "{$configuredQuestions} of {$questions->count()} questions are ready for students.",
                'passed' => $questions->isNotEmpty() && $configuredQuestions === $questions->count(),
                'action' => route('instructor.exams.question-types.index', $exam),
            ],
            [
                'key' => 'marks',
                'label' => 'Marks total',
                'description' => number_format($questionMarks, 2).' question marks of '.number_format($examMarks, 2).' configured exam marks.',
                'passed' => $questions->isNotEmpty() && abs($questionMarks - $examMarks) < 0.01,
                'action' => route('instructor.exams.questions.order.index', $exam),
            ],
            [
                'key' => 'preview',
                'label' => 'Preview as Student',
                'description' => 'The exam structure is available for final instructor review.',
                'passed' => $questions->isNotEmpty(),
                'action' => route('instructor.exams.preview.show', $exam),
            ],
        ];

        return [
            'checks' => $checks,
            'ready' => collect($checks)->every(fn (array $check): bool => $check['passed']),
            'question_count' => $questions->count(),
            'configured_question_count' => $configuredQuestions,
            'question_marks' => $questionMarks,
            'exam_marks' => $examMarks,
            'question_issues' => $questions
                ->mapWithKeys(fn ($question) => [$question->id => $this->questionIssues($question)])
                ->filter()
                ->all(),
            'question_issue_details' => $questions
                ->map(fn ($question) => [
                    'id' => $question->id,
                    'number' => $question->position,
                    'title' => $question->title ?: 'Untitled question',
                    'type' => str((string) $question->type)->replace('_', ' ')->title()->toString(),
                    'text' => $this->questionText($question),
                    'issues' => $this->questionIssues($question),
                    'action' => $this->questionEditRoute($question),
                ])
                ->filter(fn (array $question) => ! empty($question['issues']))
                ->values()
                ->all(),
        ];
    }

    private function questionIssues($question): array
    {
        $issues = [];
        $prompt = $question->prompt ?? [];
        $settings = $question->settings ?? [];
        $questionText = $this->questionText($question);

        if (blank($question->type)) {
            $issues[] = 'Choose a question type.';
        }

        if (blank($questionText)) {
            $issues[] = 'Add the question text.';
        }

        if ((float) $question->marks <= 0) {
            $issues[] = 'Enter marks greater than zero.';
        }

        if (($prompt['status'] ?? null) !== 'configured') {
            $issues[] = 'Save the question details before publishing.';
        }

        if ($question->type === 'mcq') {
            $options = collect($settings['options'] ?? [])
                ->map(fn (array $option) => trim((string) ($option['text'] ?? '')))
                ->filter();
            $uniqueOptions = $options->map(fn (string $option) => mb_strtolower($option))->unique();
            $correctCount = collect($settings['options'] ?? [])
                ->filter(fn (array $option): bool => filled($option['text'] ?? null) && (bool) ($option['is_correct'] ?? false))
                ->count();

            if ($options->count() < 2) {
                $issues[] = 'Add at least two answer choices.';
            }

            if ($uniqueOptions->count() !== $options->count()) {
                $issues[] = 'Remove duplicate answer choices.';
            }

            if ((bool) ($settings['allow_multiple_correct'] ?? false)) {
                if ($correctCount < 1) {
                    $issues[] = 'Select at least one correct answer.';
                }
            } elseif ($correctCount !== 1) {
                $issues[] = 'Select exactly one correct answer.';
            }
        }

        if (in_array($question->type, ['true_false', 'true_false_correct'], true)) {
            if (! array_key_exists('correct_answer', $settings) || ! is_bool($settings['correct_answer'])) {
                $issues[] = 'Select either True or False as the correct answer.';
            }
        }

        if ($question->type === 'essay' && (bool) ($settings['ai_grading_enabled'] ?? false)) {
            if (blank($settings['expected_answer'] ?? null)) {
                $issues[] = 'Add a model answer before using AI grading.';
            }

            if (blank($settings['rubric'] ?? null)) {
                $issues[] = 'Add rubric notes before using AI grading.';
            }

            if (blank($settings['evaluation_instructions'] ?? null)) {
                $issues[] = 'Add review guidance before using AI grading.';
            }

            if (($settings['review_guidance_status'] ?? null) !== 'approved') {
                $issues[] = 'Approve the review guidance before using AI grading.';
            }
        }

        return array_values(array_unique($issues));
    }

    private function questionText($question): string
    {
        $prompt = $question->prompt ?? [];

        return trim((string) (
            $prompt['question_text']
            ?? $prompt['statement']
            ?? $prompt['problem_statement']
            ?? $prompt['scenario']
            ?? $question->title
            ?? ''
        ));
    }

    private function questionEditRoute($question): string
    {
        $exam = $question->exam;

        return match ($question->type) {
            'mcq' => route('instructor.exams.questions.mcq.edit', [$exam, $question]),
            'true_false', 'true_false_correct' => route('instructor.exams.questions.true-false.edit', [$exam, $question]),
            'matching' => route('instructor.exams.questions.matching.edit', [$exam, $question]),
            'fill_blank' => route('instructor.exams.questions.fill-blank.edit', [$exam, $question]),
            'essay' => route('instructor.exams.questions.essay.edit', [$exam, $question]),
            'packet_tracer' => route('instructor.exams.questions.packet-tracer.edit', [$exam, $question]),
            default => $question->category === 'coding'
                ? route('instructor.exams.questions.coding.edit', [$exam, $question])
                : route('instructor.exams.question-types.index', $exam),
        };
    }
}
