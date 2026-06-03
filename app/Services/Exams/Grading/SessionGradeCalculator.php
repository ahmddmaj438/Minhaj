<?php

namespace App\Services\Exams\Grading;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use Illuminate\Support\Collection;

class SessionGradeCalculator
{
    public function calculate(ExamSession $session, callable $requiresManualGrading): SessionGradeResult
    {
        $session->loadMissing('assignment.exam.questions', 'answers.question');
        $answers = $session->answers->keyBy('instructor_exam_question_id');

        $questionResults = $session->assignment->exam->questions
            ->map(function (InstructorExamQuestion $question) use ($answers, $requiresManualGrading): QuestionGradeResult {
                $answer = $answers->get($question->id);
                $possibleMarks = max((float) $question->marks, 0.0);
                $manualPending = $requiresManualGrading($question)
                    && (! $answer instanceof ExamSessionAnswer || $answer->score === null);

                return new QuestionGradeResult(
                    questionId: (int) $question->id,
                    earnedMarks: $this->earnedMarks($answer, $possibleMarks),
                    possibleMarks: $possibleMarks,
                    manualPending: $manualPending,
                );
            })
            ->values();

        $earnedMarks = $this->roundMarks($questionResults->sum(fn (QuestionGradeResult $result): float => $result->earnedMarks));
        $possibleMarks = $this->roundMarks($questionResults->sum(fn (QuestionGradeResult $result): float => $result->possibleMarks));
        $manualPendingCount = $questionResults->filter(fn (QuestionGradeResult $result): bool => $result->manualPending)->count();
        $scoreOutOf100 = $manualPendingCount === 0 && $possibleMarks > 0
            ? round(($earnedMarks / $possibleMarks) * 100, 2)
            : null;

        return new SessionGradeResult(
            questions: $questionResults,
            earnedMarks: $earnedMarks,
            possibleMarks: $possibleMarks,
            scoreOutOf100: $scoreOutOf100,
            manualPendingCount: $manualPendingCount,
        );
    }

    public function possibleMarks(Collection $questions): float
    {
        return $this->roundMarks($questions->sum(fn (InstructorExamQuestion $question): float => max((float) $question->marks, 0.0)));
    }

    private function earnedMarks(?ExamSessionAnswer $answer, float $possibleMarks): float
    {
        if (! $answer instanceof ExamSessionAnswer || $answer->score === null) {
            return 0.0;
        }

        return $this->roundMarks(min(max((float) $answer->score, 0.0), $possibleMarks));
    }

    private function roundMarks(float $marks): float
    {
        return round($marks, 2);
    }
}
