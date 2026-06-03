<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;

interface EssayGradingProvider
{
    public function available(): bool;

    public function suggest(ExamSessionAnswer $answer): EssayGradingSuggestion;
}
