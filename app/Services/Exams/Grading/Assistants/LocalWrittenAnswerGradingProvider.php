<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;

class LocalWrittenAnswerGradingProvider implements WrittenAnswerGradingProvider
{
    public function available(): bool
    {
        return true;
    }

    public function suggest(ExamSessionAnswer $answer): WrittenAnswerGradingSuggestion
    {
        return $this->suggestFallback($answer);
    }

    public function suggestFallback(
        ExamSessionAnswer $answer,
        ?string $providerNote = null,
        ?string $providerError = null,
    ): WrittenAnswerGradingSuggestion
    {
        $answer->loadMissing('question');
        $question = $answer->question;
        $maxScore = (float) ($question?->marks ?? 0);

        return new WrittenAnswerGradingSuggestion(
            suggestedScore: null,
            maxScore: $maxScore,
            confidence: 0.0,
            feedback: 'No AI evaluation was generated because no real AI provider completed the request.',
            strengths: [],
            improvements: [
                'Configure a real AI provider key, such as GOOGLE_GEMINI_API_KEY, to send the JSON evaluation package for content-based grading.',
                'The assistant no longer creates local scores from answer length.',
            ],
            provider: 'ai_provider_unavailable',
            rationale: 'No content-based grade was calculated locally. This assistant now requires a real AI provider response because the score must be based on the structured JSON evaluation package, rubric, expected answer, and student answer evidence.',
            providerNote: $providerNote ?? 'No AI provider is configured. Add GOOGLE_GEMINI_API_KEY to send the grading JSON to Google Gemini.',
            providerError: $providerError,
        );
    }
}
