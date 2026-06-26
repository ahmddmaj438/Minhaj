<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiConnectionTester
{
    private const TEST_PROMPT = 'Reply with: AI configuration is working.';
    private const EXPECTED_RESPONSE = 'AI configuration is working';

    public function test(string $provider, string $apiKey, ?string $baseUrl, string $model): string
    {
        try {
            $response = match ($provider) {
                'openai' => $this->openAi($apiKey, $baseUrl, $model),
                'gemini' => $this->gemini($apiKey, $baseUrl, $model),
                'pollinations' => $this->pollinations($apiKey, $baseUrl, $model),
                'claude' => $this->claude($apiKey, $baseUrl, $model),
                'custom' => $this->custom($apiKey, $baseUrl, $model),
                default => throw new RuntimeException('Unsupported AI provider.'),
            };
        } catch (\Illuminate\Http\Client\ConnectionException) {
            throw new RuntimeException('AI service is currently unavailable.');
        }

        if (! $response->successful()) {
            Log::warning('AI configuration test was rejected by provider.', [
                'provider' => $provider,
                'model' => $model,
                'status' => $response->status(),
                'body' => str($response->body())->limit(500)->toString(),
            ]);

            throw new RuntimeException('AI service rejected the key or model.');
        }

        $text = match ($provider) {
            'openai', 'custom' => data_get($response->json(), 'choices.0.message.content'),
            'gemini' => null,
            'pollinations' => $this->pollinationsText($response->json(), $response->body()),
            'claude' => data_get($response->json(), 'content.0.text'),
            default => null,
        };

        if ($provider === 'gemini') {
            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        }

        if (! is_string($text)) {
            Log::warning('AI configuration test returned an unreadable response.', [
                'provider' => $provider,
                'model' => $model,
                'body' => str($response->body())->limit(500)->toString(),
            ]);

            throw new RuntimeException('AI test failed. Please review the settings.');
        }

        if (trim($text) === '') {
            throw new RuntimeException('AI response was empty. Please check the configuration.');
        }

        if (! str_contains(strtolower($text), strtolower(self::EXPECTED_RESPONSE))) {
            Log::warning('AI configuration test response did not match the expected phrase.', [
                'provider' => $provider,
                'model' => $model,
                'response' => str($text)->limit(500)->toString(),
            ]);

            throw new RuntimeException('AI test failed. Please review the settings.');
        }

        return 'AI connection successful.';
    }

    private function openAi(string $apiKey, ?string $baseUrl, string $model): Response
    {
        return Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(rtrim($baseUrl ?: 'https://api.openai.com/v1', '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => self::TEST_PROMPT],
                ],
                'max_tokens' => 10,
            ]);
    }

    private function gemini(string $apiKey, ?string $baseUrl, string $model): Response
    {
        $url = rtrim($baseUrl ?: 'https://generativelanguage.googleapis.com', '/')
            .'/v1beta/models/'.rawurlencode($model).':generateContent';

        return Http::timeout(15)
            ->acceptJson()
            ->post($url.'?key='.rawurlencode($apiKey), [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => self::TEST_PROMPT],
                        ],
                    ],
                ],
            ]);
    }

    private function claude(string $apiKey, ?string $baseUrl, string $model): Response
    {
        return Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->post(rtrim($baseUrl ?: 'https://api.anthropic.com/v1', '/').'/messages', [
                'model' => $model,
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => self::TEST_PROMPT],
                ],
            ]);
    }

    private function pollinations(string $apiKey, ?string $baseUrl, string $model): Response
    {
        $request = Http::timeout(15)->acceptJson();

        if (filled($apiKey)) {
            $request = $request->withToken($apiKey);
        }

        return $request->get(rtrim($baseUrl ?: 'https://text.pollinations.ai', '/').'/'.rawurlencode(self::TEST_PROMPT), [
            'model' => $model ?: 'openai',
        ]);
    }

    private function custom(string $apiKey, ?string $baseUrl, string $model): Response
    {
        if (blank($baseUrl)) {
            throw new RuntimeException('AI test failed. Please review the settings.');
        }

        return Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(rtrim($baseUrl, '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => self::TEST_PROMPT],
                ],
                'max_tokens' => 10,
            ]);
    }

    private function pollinationsText(mixed $json, string $body): ?string
    {
        if (is_array($json)) {
            return data_get($json, 'text') ?? data_get($json, 'choices.0.message.content');
        }

        return trim($body) !== '' ? $body : null;
    }
}
