<?php

namespace App\Services\Exams\Grading\Assistants;

class WrittenAnswerGradingSuggestion
{
    /**
     * @param list<string> $strengths
     * @param list<string> $improvements
     */
    public function __construct(
        public readonly ?float $suggestedScore,
        public readonly float $maxScore,
        public readonly float $confidence,
        public readonly string $feedback,
        public readonly array $strengths,
        public readonly array $improvements,
        public readonly string $provider,
        public readonly ?string $rationale = null,
        public readonly ?string $providerNote = null,
        public readonly ?string $providerError = null,
        public readonly array $rubricAssessment = [],
    ) {}

    public function toArray(): array
    {
        return [
            'suggested_score' => $this->suggestedScore,
            'max_score' => $this->maxScore,
            'confidence' => $this->confidence,
            'feedback' => $this->feedback,
            'strengths' => $this->strengths,
            'improvements' => $this->improvements,
            'provider' => $this->provider,
            'rationale' => $this->rationale,
            'provider_note' => $this->providerNote,
            'provider_error' => $this->providerError,
            'rubric_assessment' => $this->rubricAssessment,
            'assist_scope' => 'written_answer',
            'generated_at' => now()->toISOString(),
        ];
    }
}
