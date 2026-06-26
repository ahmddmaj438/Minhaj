<?php

namespace App\Http\Requests\Admin;

use App\Models\AiConfiguration;
use App\Support\AI\AiProviderCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAiConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('admin.settings.ai-configuration.test')
            ? 'button.admin.settings.ai-configuration.test'
            : 'button.admin.settings.ai-configuration.save';

        return $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        $storedConfiguration = AiConfiguration::query()
            ->where('provider', $this->input('provider'))
            ->first();

        return [
            'provider' => ['required', Rule::in(AiProviderCatalog::keys())],
            'api_key' => [
                Rule::requiredIf(AiProviderCatalog::requiresApiKey((string) $this->input('provider')) && ! $storedConfiguration?->api_key),
                'nullable',
                'string',
                'max:10000',
            ],
            'model_name' => ['required', 'string', 'max:150'],
            'base_url' => [
                Rule::requiredIf($this->input('provider') === 'custom'),
                'nullable',
                'url:http,https',
                'max:500',
            ],
            'status' => ['required', Rule::in([
                AiConfiguration::STATUS_ACTIVE,
                AiConfiguration::STATUS_INACTIVE,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'api_key.required' => 'API key is missing.',
            'model_name.required' => 'Please enter the model name.',
            'base_url.required' => 'Please enter the provider URL.',
            'provider.in' => 'AI test failed. Please review the settings.',
        ];
    }
}
