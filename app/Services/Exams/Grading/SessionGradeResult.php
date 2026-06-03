<?php

namespace App\Services\Exams\Grading;

use Illuminate\Support\Collection;

class SessionGradeResult
{
    /**
     * @param Collection<int, QuestionGradeResult> $questions
     */
    public function __construct(
        public readonly Collection $questions,
        public readonly float $earnedMarks,
        public readonly float $possibleMarks,
        public readonly ?float $scoreOutOf100,
        public readonly int $manualPendingCount,
    ) {}

    public function hasManualPending(): bool
    {
        return $this->manualPendingCount > 0;
    }
}
