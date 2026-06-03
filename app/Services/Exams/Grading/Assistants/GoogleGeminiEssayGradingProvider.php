<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\ExamSessionAnswer;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleGeminiEssayGradingProvider implements EssayGradingProvider
{
    public function available(): bool
    {
        return filled($this->apiKey());
    }

    public function suggest(ExamSessionAnswer $answer): EssayGradingSuggestion
    {
        if (! $this->available()) {
            throw new RuntimeException('Google Gemini API key is not configured.');
        }

        $answer->loadMissing('question.sessionAnswers', 'session.assignment.exam.course', 'session.student.user');
        $question = $answer->question;
        $maxScore = (float) $question->marks;
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->prompt($answer)],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->responseSchema(),
            ],
        ];

        $response = Http::timeout((int) config('services.ai_grading.google.timeout', 30))
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey(),
                'Content-Type' => 'application/json',
            ])
            ->post($this->endpoint(), $payload);

        if ($response->failed()) {
            throw new RuntimeException('Google Gemini grading request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        $data = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($data)) {
            throw new RuntimeException('Google Gemini returned an invalid grading response.');
        }

        return new EssayGradingSuggestion(
            suggestedScore: min(max((float) ($data['suggested_score'] ?? 0), 0), $maxScore),
            maxScore: $maxScore,
            confidence: min(max((float) ($data['confidence'] ?? 0.5), 0), 1),
            feedback: trim((string) ($data['feedback'] ?? 'Review suggested score before saving.')),
            strengths: $this->stringList($data['strengths'] ?? []),
            improvements: $this->stringList($data['improvements'] ?? []),
            provider: 'google_gemini:'.$this->model(),
        );
    }

    private function prompt(ExamSessionAnswer $answer): string
    {
        $question = $answer->question;
        $settings = $question->settings ?? [];
        $prompt = $question->prompt ?? [];
        $studentAnswer = (string) ($answer->answer_payload['value']['response'] ?? '');

        return json_encode([
            'task' => 'Assist an instructor with grading an essay or short written answer. Return JSON only.',
            'rules' => [
                'Do not grade outside the max score.',
                'Use the question, rubric, difficulty, expected answer, and student answer.',
                'Be conservative. The instructor makes the final decision.',
                'Return: suggested_score, confidence, feedback, strengths, improvements.',
            ],
            'grading_context' => [
                'current_score' => $answer->score !== null ? (float) $answer->score : null,
                'current_feedback' => $answer->feedback,
                'max_score' => (float) $question->marks,
                'score_scale' => 'question_marks',
            ],
            'question' => [
                'type' => $question->type,
                'title' => $question->title,
                'text' => $prompt['question_text'] ?? $question->title,
                'instructions' => $prompt['instructions'] ?? null,
                'difficulty' => $question->difficulty,
                'topic' => $question->topic,
                'max_score' => (float) $question->marks,
                'expected_answer' => $settings['expected_answer'] ?? null,
                'rubric' => $settings['rubric'] ?? null,
                'min_words' => $settings['min_words'] ?? null,
                'max_words' => $settings['max_words'] ?? null,
            ],
            'student_answer' => $studentAnswer,
        ], JSON_PRETTY_PRINT);
    }

    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'suggested_score' => [
                    'type' => 'number',
                    'description' => 'Suggested score in question marks. Must be between 0 and max_score.',
                ],
                'confidence' => [
                    'type' => 'number',
                    'description' => 'Confidence from 0 to 1.',
                ],
                'feedback' => [
                    'type' => 'string',
                    'description' => 'Concise instructor-facing feedback explaining the suggested score.',
                ],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'improvements' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['suggested_score', 'confidence', 'feedback', 'strengths', 'improvements'],
        ];
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.ai_grading.google.endpoint'), '/')
            .'/v1beta/models/'.$this->model().':generateContent';
    }

    private function apiKey(): ?string
    {
        return config('services.ai_grading.google.api_key');
    }

    private function model(): string
    {
        return (string) config('services.ai_grading.google.model', 'gemini-2.5-flash');
    }

    private function stringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }
}
