<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;

class LocalEssayGradingProvider implements EssayGradingProvider
{
    public function available(): bool
    {
        return true;
    }

    public function suggest(ExamSessionAnswer $answer): EssayGradingSuggestion
    {
        $answer->loadMissing('question');
        $question = $answer->question;
        $maxScore = (float) ($question?->marks ?? 0);

        return new EssayGradingSuggestion(
            suggestedScore: null,
            maxScore: $maxScore,
            confidence: 0.0,
            feedback: 'No AI evaluation was generated. Please review this answer manually or try again after AI assistance is configured.',
            strengths: [],
            improvements: [
                'Check the AI assistance settings before generating another suggestion.',
                'Use the question rubric and student answer evidence to enter the final mark.',
            ],
            provider: 'ai_provider_unavailable',
            rationale: 'No content-based score was calculated because the AI service did not complete the evaluation.',
            providerNote: 'The AI service is not ready yet. Please check the AI assistance settings.',
        );
    }
}
