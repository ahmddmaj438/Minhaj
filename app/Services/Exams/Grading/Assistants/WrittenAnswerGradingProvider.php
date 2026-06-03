<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;

interface WrittenAnswerGradingProvider
{
    public function available(): bool;

    public function suggest(ExamSessionAnswer $answer): WrittenAnswerGradingSuggestion;
}
