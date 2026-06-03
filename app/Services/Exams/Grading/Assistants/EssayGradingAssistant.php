<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;

class EssayGradingAssistant
{
    public function __construct(
        private readonly WrittenAnswerGradingAssistant $assistant,
    ) {}

    public function supports(ExamSessionAnswer $answer): bool
    {
        return $this->assistant->supports($answer);
    }

    public function suggest(ExamSessionAnswer $answer): EssayGradingSuggestion
    {
        $suggestion = $this->assistant->suggest($answer);

        return new EssayGradingSuggestion(
            suggestedScore: $suggestion->suggestedScore,
            maxScore: $suggestion->maxScore,
            confidence: $suggestion->confidence,
            feedback: $suggestion->feedback,
            strengths: $suggestion->strengths,
            improvements: $suggestion->improvements,
            provider: $suggestion->provider,
            rationale: $suggestion->rationale,
            providerNote: $suggestion->providerNote,
            providerError: $suggestion->providerError,
            rubricAssessment: $suggestion->rubricAssessment,
        );
    }
}
