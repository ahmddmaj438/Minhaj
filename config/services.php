<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai_grading' => [
        'provider' => env('AI_GRADING_PROVIDER', 'auto'),
        'google' => [
            'api_key' => env('GOOGLE_GEMINI_API_KEY'),
            'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-2.5-flash'),
            'endpoint' => env('GOOGLE_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com'),
            'timeout' => env('GOOGLE_GEMINI_TIMEOUT', 30),
        ],
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
            'endpoint' => env('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
            'timeout' => env('GROQ_TIMEOUT', 30),
        ],
        'pollinations' => [
            'enabled' => env('POLLINATIONS_ENABLED', true),
            'model' => env('POLLINATIONS_MODEL', 'openai'),
            'models' => explode(',', env('POLLINATIONS_MODELS', 'openai,mistral')),
            'endpoint' => env('POLLINATIONS_ENDPOINT', 'https://text.pollinations.ai'),
            'timeout' => env('POLLINATIONS_TIMEOUT', 18),
            'max_attempts' => env('POLLINATIONS_MAX_ATTEMPTS', 2),
            'retry_delay_seconds' => env('POLLINATIONS_RETRY_DELAY_SECONDS', 1),
            'runtime_budget_seconds' => env('POLLINATIONS_RUNTIME_BUDGET_SECONDS', 26),
            'ssl_retry_without_verification' => env('POLLINATIONS_SSL_RETRY_WITHOUT_VERIFICATION', true),
        ],
    ],

    'ai' => [
        'active_provider' => env('AI_PROVIDER', 'pollinations'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'openai'),
        'base_url' => env('AI_API_URL', 'https://text.pollinations.ai'),
    ],

];
