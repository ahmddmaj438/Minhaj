<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSessionAnswer;

class WrittenAnswerSupport
{
    /**
     * MCQ and plain true/false are selected with controls. Every other current
     * student question renderer contains a typed field.
     */
    private const NON_KEYBOARD_TYPES = [
        'mcq',
        'true_false',
    ];

    public function supportsAnswer(ExamSessionAnswer $answer): bool
    {
        $answer->loadMissing('question');

        return $this->supportsQuestion($answer->question);
    }

    public function supportsQuestion(?InstructorExamQuestion $question): bool
    {
        if (! $question instanceof InstructorExamQuestion) {
            return false;
        }

        return ! in_array($question->type, self::NON_KEYBOARD_TYPES, true);
    }

    public function answerFormat(?InstructorExamQuestion $question): string
    {
        if (! $question instanceof InstructorExamQuestion) {
            return 'unknown';
        }

        return match (true) {
            $question->type === 'essay' => 'essay_or_short_written_response',
            $question->category === 'coding' => 'code_written_in_editor',
            $question->type === 'packet_tracer' => 'network_lab_written_response',
            $question->type === 'fill_blank' => 'fill_blank_text_entries',
            $question->type === 'matching' => 'matching_text_entries',
            $question->type === 'true_false_correct' => 'true_false_with_written_correction',
            default => 'written_response',
        };
    }

    public function questionText(?InstructorExamQuestion $question): string
    {
        if (! $question instanceof InstructorExamQuestion) {
            return '';
        }

        $prompt = $question->prompt ?? [];

        return trim((string) (
            $prompt['question_text']
            ?? $prompt['statement']
            ?? $prompt['problem_statement']
            ?? $prompt['scenario']
            ?? $question->title
        ));
    }

    public function answerText(ExamSessionAnswer $answer): string
    {
        $answer->loadMissing('question');
        $question = $answer->question;
        $value = $answer->answer_payload['value'] ?? [];

        if (! is_array($value)) {
            return trim((string) $value);
        }

        if (array_key_exists('response', $value)) {
            return trim((string) ($value['response'] ?? ''));
        }

        if ($question?->type === 'true_false_correct') {
            return trim(implode("\n", array_filter([
                'Selected answer: '.($value['answer'] ?? '[not selected]'),
                'Correction: '.($value['correction'] ?? '[blank]'),
            ])));
        }

        if (array_key_exists('matches', $value)) {
            return $this->formatMatchingAnswer($question, $value['matches']);
        }

        if (array_key_exists('blanks', $value)) {
            return $this->formatFillBlankAnswer($question, $value['blanks']);
        }

        if (array_key_exists('correction', $value)) {
            return trim((string) ($value['correction'] ?? ''));
        }

        return trim((string) json_encode($value, JSON_PRETTY_PRINT));
    }

    private function formatMatchingAnswer(?InstructorExamQuestion $question, mixed $matches): string
    {
        $pairs = collect($question?->settings['pairs'] ?? []);

        return collect(is_array($matches) ? $matches : [])
            ->map(function ($match, int|string $index) use ($pairs): string {
                $position = is_numeric($index) ? (int) $index : 0;
                $pair = $pairs->get($position, []);
                $left = is_array($pair) ? ($pair['left'] ?? 'Item '.($position + 1)) : 'Item '.($position + 1);

                return $left.': '.$this->displayAnswerValue($match);
            })
            ->implode("\n");
    }

    private function formatFillBlankAnswer(?InstructorExamQuestion $question, mixed $blanks): string
    {
        $configuredBlanks = collect($question?->settings['blanks'] ?? []);

        return collect(is_array($blanks) ? $blanks : [])
            ->map(function ($blank, int|string $index) use ($configuredBlanks): string {
                $position = is_numeric($index) ? (int) $index : 0;
                $configuredBlank = $configuredBlanks->get($position, []);
                $label = is_array($configuredBlank) ? ($configuredBlank['label'] ?? 'Blank '.($position + 1)) : 'Blank '.($position + 1);

                return $label.': '.$this->displayAnswerValue($blank);
            })
            ->implode("\n");
    }

    private function displayAnswerValue(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : '[blank]';
    }
}
