<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiConnectionTester
{
    public function test(string $provider, string $apiKey, ?string $baseUrl, string $model): string
    {
        $response = match ($provider) {
            'openai' => $this->openAi($apiKey, $baseUrl),
            'gemini' => $this->gemini($apiKey, $baseUrl, $model),
            'claude' => $this->claude($apiKey, $baseUrl),
            'custom' => $this->custom($apiKey, $baseUrl),
            default => throw new RuntimeException('Unsupported AI provider.'),
        };

        if (! $response->successful()) {
            throw new RuntimeException(
                'Connection failed with HTTP '.$response->status().'. Check the key, model, and base URL.'
            );
        }

        return 'Connection successful. The provider accepted the configuration.';
    }

    private function openAi(string $apiKey, ?string $baseUrl): Response
    {
        return Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->get(rtrim($baseUrl ?: 'https://api.openai.com/v1', '/').'/models');
    }

    private function gemini(string $apiKey, ?string $baseUrl, string $model): Response
    {
        $url = rtrim($baseUrl ?: 'https://generativelanguage.googleapis.com', '/')
            .'/v1beta/models/'.rawurlencode($model);

        return Http::timeout(15)
            ->acceptJson()
            ->get($url, ['key' => $apiKey]);
    }

    private function claude(string $apiKey, ?string $baseUrl): Response
    {
        return Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->get(rtrim($baseUrl ?: 'https://api.anthropic.com/v1', '/').'/models');
    }

    private function custom(string $apiKey, ?string $baseUrl): Response
    {
        if (blank($baseUrl)) {
            throw new RuntimeException('A base URL is required for a custom provider.');
        }

        return Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->get(rtrim($baseUrl, '/').'/models');
    }
}
