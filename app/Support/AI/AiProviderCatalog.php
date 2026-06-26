<?php

namespace App\Support\AI;

final class AiProviderCatalog
{
    public static function all(): array
    {
        return [
            'openai' => [
                'label' => 'OpenAI',
                'default_model' => 'gpt-4.1-mini',
                'default_base_url' => 'https://api.openai.com/v1',
                'help' => 'Use an OpenAI API key and a model available to your organization.',
            ],
            'gemini' => [
                'label' => 'Gemini',
                'default_model' => 'gemini-2.5-flash',
                'default_base_url' => 'https://generativelanguage.googleapis.com',
                'help' => 'Use a Google AI Studio or Google Cloud Gemini API key.',
            ],
            'pollinations' => [
                'label' => 'Pollinations Public',
                'default_model' => 'openai',
                'default_base_url' => 'https://text.pollinations.ai',
                'help' => 'Free public testing option. It does not require a stored key, but availability can vary during busy periods.',
                'api_key_optional' => true,
            ],
            'claude' => [
                'label' => 'Claude',
                'default_model' => 'claude-sonnet-4-20250514',
                'default_base_url' => 'https://api.anthropic.com/v1',
                'help' => 'Use an Anthropic API key and an enabled Claude model.',
            ],
            'custom' => [
                'label' => 'Custom Provider',
                'default_model' => '',
                'default_base_url' => '',
                'help' => 'Use an OpenAI-compatible provider endpoint managed by your institution.',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function find(string $provider): array
    {
        return self::all()[$provider] ?? self::all()['custom'];
    }

    public static function requiresApiKey(string $provider): bool
    {
        return ! (bool) (self::find($provider)['api_key_optional'] ?? false);
    }
}
