<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionResponseManager
{
    public function createDraftAnswers(ExamSession $session): void
    {
        $questions = $session->assignment
            ->exam
            ->questions;

        foreach ($questions as $question) {
            ExamSessionAnswer::firstOrCreate(
                [
                    'exam_session_id' => $session->id,
                    'instructor_exam_question_id' => $question->id,
                ],
                [
                    'answer_payload' => [
                        'status' => 'draft',
                        'question_type' => $question->type,
                    ],
                ]
            );
        }
    }

    public function saveDrafts(ExamSession $session, array $answers): void
    {
        $questions = $session->assignment
            ->exam
            ->questions
            ->keyBy('id');

        DB::transaction(function () use ($session, $answers, $questions): void {
            foreach ($answers as $questionId => $answer) {
                $question = $questions->get((int) $questionId);

                if (! $question instanceof InstructorExamQuestion) {
                    throw ValidationException::withMessages([
                        'answers' => 'One of the submitted answers does not belong to this exam session.',
                    ]);
                }

                ExamSessionAnswer::updateOrCreate(
                    [
                        'exam_session_id' => $session->id,
                        'instructor_exam_question_id' => $question->id,
                    ],
                    [
                        'answer_payload' => [
                            'status' => 'draft',
                            'question_type' => $question->type,
                            'value' => $this->normalize($question, is_array($answer) ? $answer : []),
                            'saved_at' => now()->toISOString(),
                        ],
                        'answered_at' => now(),
                    ]
                );
            }
        });
    }

    private function normalize(InstructorExamQuestion $question, array $answer): array
    {
        return match ($question->type) {
            'mcq' => [
                'selected_options' => collect($answer['selected_options'] ?? [])
                    ->map(fn ($value): string => trim((string) $value))
                    ->filter()
                    ->values()
                    ->all(),
            ],
            'true_false', 'true_false_correct' => [
                'answer' => in_array(($answer['answer'] ?? null), ['true', 'false'], true) ? $answer['answer'] : null,
                'correction' => trim((string) ($answer['correction'] ?? '')) ?: null,
            ],
            'matching' => [
                'matches' => collect($answer['matches'] ?? [])
                    ->map(fn ($value): ?string => trim((string) $value) ?: null)
                    ->all(),
            ],
            'fill_blank' => [
                'blanks' => collect($answer['blanks'] ?? [])
                    ->map(fn ($value): ?string => trim((string) $value) ?: null)
                    ->all(),
            ],
            default => [
                'response' => trim((string) ($answer['response'] ?? '')) ?: null,
            ],
        };
    }
}
