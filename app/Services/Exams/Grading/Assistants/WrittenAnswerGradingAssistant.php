<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WrittenAnswerGradingAssistant
{
    public function __construct(
        private readonly GoogleGeminiWrittenAnswerGradingProvider $google,
        private readonly GroqWrittenAnswerGradingProvider $groq,
        private readonly PollinationsPublicWrittenAnswerGradingProvider $pollinations,
        private readonly LocalWrittenAnswerGradingProvider $local,
        private readonly WrittenAnswerSupport $support,
    ) {}

    public function supports(ExamSessionAnswer $answer): bool
    {
        return $this->support->supportsAnswer($answer);
    }

    public function suggest(ExamSessionAnswer $answer): WrittenAnswerGradingSuggestion
    {
        $answer->loadMissing('question');
        if (! $this->support->supportsAnswer($answer)) {
            throw new RuntimeException('AI grading assistance is available only for keyboard-written answers.');
        }

        $errors = [];

        foreach ($this->providerChain() as $name => $provider) {
            if (! $provider->available()) {
                $errors[] = $name.' API key is not configured.';

                continue;
            }

            try {
                return $provider->suggest($answer);
            } catch (Throwable $exception) {
                Log::warning($name.' written-answer grading failed.', [
                    'answer_id' => $answer->id,
                    'question_id' => $answer->instructor_exam_question_id,
                    'error' => $exception->getMessage(),
                ]);

                $errors[] = $name.': '.$this->publicError($exception);
            }
        }

        return $this->local->suggestFallback(
            $answer,
            'The AI service is currently unavailable. Please check the AI assistance settings and try again.',
            implode(' | ', array_filter($errors)),
        );
    }

    /**
     * @return array<string, WrittenAnswerGradingProvider>
     */
    private function providerChain(): array
    {
        return match ((string) config('services.ai_grading.provider', 'auto')) {
            'google_gemini' => ['Google Gemini' => $this->google],
            'groq' => ['Groq' => $this->groq],
            'pollinations' => ['Pollinations public' => $this->pollinations],
            default => [
                'Google Gemini' => $this->google,
                'Groq' => $this->groq,
                'Pollinations public' => $this->pollinations,
            ],
        };
    }

    private function publicError(Throwable $exception): string
    {
        return 'The AI service could not complete the request. Please check the AI assistance settings or try again later.';
    }
}
