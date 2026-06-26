<?php

namespace App\Services\AI;

use App\Models\AiConfiguration;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiConfigurationManager
{
    public function active(): ?AiConfiguration
    {
        try {
            if (! Schema::hasTable('ai_configurations')) {
                return null;
            }

            return AiConfiguration::query()
                ->where('status', AiConfiguration::STATUS_ACTIVE)
                ->latest('updated_at')
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    public function apply(): void
    {
        $configuration = $this->active();

        if (! $configuration) {
            return;
        }

        if (blank($configuration->api_key) && $configuration->provider !== 'pollinations') {
            return;
        }

        config([
            'services.ai.active_provider' => $configuration->provider,
            'services.ai.api_key' => $configuration->api_key,
            'services.ai.model' => $configuration->model_name,
            'services.ai.base_url' => $configuration->base_url,
        ]);

        if ($configuration->provider === 'gemini') {
            config([
                'services.ai_grading.provider' => 'google_gemini',
                'services.ai_grading.google.api_key' => $configuration->api_key,
                'services.ai_grading.google.model' => $configuration->model_name,
                'services.ai_grading.google.endpoint' => rtrim(
                    $configuration->base_url ?: 'https://generativelanguage.googleapis.com',
                    '/'
                ),
            ]);
        }

        if ($configuration->provider === 'pollinations') {
            config([
                'services.ai_grading.provider' => 'pollinations',
                'services.ai_grading.pollinations.enabled' => true,
                'services.ai_grading.pollinations.model' => $configuration->model_name,
                'services.ai_grading.pollinations.endpoint' => rtrim(
                    $configuration->base_url ?: 'https://text.pollinations.ai',
                    '/'
                ),
            ]);
        }
    }
}
