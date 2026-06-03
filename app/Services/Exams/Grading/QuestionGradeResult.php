<?php

namespace App\Services\Exams\Grading;

class QuestionGradeResult
{
    public function __construct(
        public readonly int $questionId,
        public readonly float $earnedMarks,
        public readonly float $possibleMarks,
        public readonly bool $manualPending,
    ) {}
}
