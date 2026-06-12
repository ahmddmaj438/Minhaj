<?php

namespace Tests\Feature;

use App\Models\AiConfiguration;
use App\Models\User;
use App\Services\AI\AiConfigurationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_an_encrypted_active_ai_configuration(): void
    {
        $admin = User::factory()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.settings.ai-configuration.update'), [
                'provider' => 'gemini',
                'api_key' => 'secret-gemini-key',
                'model_name' => 'gemini-2.5-flash',
                'base_url' => 'https://generativelanguage.googleapis.com',
                'status' => AiConfiguration::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.settings.ai-configuration.edit', ['provider' => 'gemini']));

        $configuration = AiConfiguration::firstOrFail();

        $this->assertSame('secret-gemini-key', $configuration->api_key);
        $this->assertNotSame(
            'secret-gemini-key',
            DB::table('ai_configurations')->where('id', $configuration->id)->value('api_key')
        );
        $this->assertSame(AiConfiguration::STATUS_ACTIVE, $configuration->status);
    }

    public function test_activating_a_provider_deactivates_other_providers(): void
    {
        $admin = User::factory()->create();

        AiConfiguration::create([
            'provider' => 'openai',
            'api_key' => 'openai-key',
            'model_name' => 'gpt-4.1-mini',
            'base_url' => 'https://api.openai.com/v1',
            'status' => AiConfiguration::STATUS_ACTIVE,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.settings.ai-configuration.update'), [
                'provider' => 'claude',
                'api_key' => 'claude-key',
                'model_name' => 'claude-sonnet-4-20250514',
                'base_url' => 'https://api.anthropic.com/v1',
                'status' => AiConfiguration::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_configurations', [
            'provider' => 'openai',
            'status' => AiConfiguration::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseHas('ai_configurations', [
            'provider' => 'claude',
            'status' => AiConfiguration::STATUS_ACTIVE,
        ]);
    }

    public function test_active_gemini_configuration_is_applied_to_existing_grading_config(): void
    {
        AiConfiguration::create([
            'provider' => 'gemini',
            'api_key' => 'database-gemini-key',
            'model_name' => 'gemini-test-model',
            'base_url' => 'https://gemini.example.test',
            'status' => AiConfiguration::STATUS_ACTIVE,
        ]);

        app(AiConfigurationManager::class)->apply();

        $this->assertSame('gemini', config('services.ai.active_provider'));
        $this->assertSame('database-gemini-key', config('services.ai.api_key'));
        $this->assertSame('google_gemini', config('services.ai_grading.provider'));
        $this->assertSame('database-gemini-key', config('services.ai_grading.google.api_key'));
        $this->assertSame('gemini-test-model', config('services.ai_grading.google.model'));
        $this->assertSame('https://gemini.example.test', config('services.ai_grading.google.endpoint'));
    }

    public function test_admin_can_test_an_ai_connection_without_saving_the_key(): void
    {
        Http::fake([
            'https://api.openai.com/v1/models' => Http::response(['data' => []]),
        ]);

        $admin = User::factory()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.settings.ai-configuration.test'), [
                'provider' => 'openai',
                'api_key' => 'temporary-test-key',
                'model_name' => 'gpt-4.1-mini',
                'base_url' => 'https://api.openai.com/v1',
                'status' => AiConfiguration::STATUS_INACTIVE,
            ])
            ->assertRedirect()
            ->assertSessionHas('connection_status');

        $this->assertDatabaseCount('ai_configurations', 0);
        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://api.openai.com/v1/models'
            && $request->hasHeader('Authorization', 'Bearer temporary-test-key')
        );
    }

    public function test_api_key_is_not_flashed_after_validation_failure(): void
    {
        $admin = User::factory()->create();

        $this
            ->actingAs($admin)
            ->from(route('admin.settings.ai-configuration.edit'))
            ->post(route('admin.settings.ai-configuration.update'), [
                'provider' => 'openai',
                'api_key' => 'secret-that-must-not-be-flashed',
                'model_name' => '',
                'base_url' => 'https://api.openai.com/v1',
                'status' => AiConfiguration::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.settings.ai-configuration.edit'))
            ->assertSessionHasErrors('model_name')
            ->assertSessionMissing('_old_input.api_key');
    }

    public function test_unknown_provider_query_falls_back_to_supported_provider(): void
    {
        $admin = User::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('admin.settings.ai-configuration.edit', ['provider' => 'unsupported']))
            ->assertOk()
            ->assertSee('OpenAI')
            ->assertDontSee('value="unsupported"', false);
    }
}
