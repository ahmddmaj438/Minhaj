<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExam;

class ExamPublishReadiness
{
    public function inspect(InstructorExam $exam): array
    {
        $questions = $exam->questions()->get();
        $questionMarks = (float) $questions->sum(fn ($question): float => (float) $question->marks);
        $examMarks = (float) $exam->total_marks;
        $configuredQuestions = $questions->filter(
            fn ($question): bool => ($question->prompt['status'] ?? null) === 'configured'
        )->count();

        $checks = [
            [
                'key' => 'information',
                'label' => 'Exam information',
                'description' => 'Title, course, duration, and total marks are complete.',
                'passed' => filled($exam->title)
                    && $exam->course_id !== null
                    && $exam->duration_minutes >= 5
                    && $examMarks > 0,
                'action' => route('instructor.exams.edit', $exam),
            ],
            [
                'key' => 'format',
                'label' => 'Exam format',
                'description' => 'A student display format has been selected.',
                'passed' => filled($exam->display_format),
                'action' => route('instructor.exams.edit', $exam).'#exam-format',
            ],
            [
                'key' => 'questions',
                'label' => 'Question management',
                'description' => $questions->isEmpty()
                    ? 'Add at least one question.'
                    : "{$configuredQuestions} of {$questions->count()} questions are fully configured.",
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
                'label' => 'Preview',
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
        ];
    }
}
