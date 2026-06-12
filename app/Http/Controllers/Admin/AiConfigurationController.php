<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiConfigurationRequest;
use App\Models\AiConfiguration;
use App\Services\AI\AiConfigurationManager;
use App\Services\AI\AiConnectionTester;
use App\Support\AI\AiProviderCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AiConfigurationController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->can('screen.admin.settings.ai-configuration.edit.view'), 403);

        $configurations = AiConfiguration::query()->get()->keyBy('provider');
        $defaultProvider = $configurations
            ->firstWhere('status', AiConfiguration::STATUS_ACTIVE)?->provider ?? 'openai';
        $selectedProvider = request()->string('provider')->toString();

        if (! in_array($selectedProvider, AiProviderCatalog::keys(), true)) {
            $selectedProvider = $defaultProvider;
        }

        return view('admin.settings.ai-configuration', [
            'providers' => AiProviderCatalog::all(),
            'configurations' => $configurations,
            'selectedProvider' => $selectedProvider,
        ]);
    }

    public function update(
        SaveAiConfigurationRequest $request,
        AiConfigurationManager $manager
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            if ($validated['status'] === AiConfiguration::STATUS_ACTIVE) {
                AiConfiguration::query()->update(['status' => AiConfiguration::STATUS_INACTIVE]);
            }

            $configuration = AiConfiguration::query()->firstOrNew([
                'provider' => $validated['provider'],
            ]);

            $configuration->fill([
                'model_name' => $validated['model_name'],
                'base_url' => $validated['base_url'] ?: null,
                'status' => $validated['status'],
                'updated_by' => $request->user()->id,
            ]);

            if (filled($validated['api_key'] ?? null)) {
                $configuration->api_key = $validated['api_key'];
            }

            $configuration->save();
        });

        $manager->apply();

        return redirect()
            ->route('admin.settings.ai-configuration.edit', ['provider' => $validated['provider']])
            ->with('status', 'AI configuration saved securely.');
    }

    public function test(
        SaveAiConfigurationRequest $request,
        AiConnectionTester $tester
    ): RedirectResponse {
        abort_unless($request->user()?->can('button.admin.settings.ai-configuration.test'), 403);

        $validated = $request->validated();
        $stored = AiConfiguration::query()->where('provider', $validated['provider'])->first();
        $apiKey = $validated['api_key'] ?: $stored?->api_key;

        if (blank($apiKey)) {
            throw ValidationException::withMessages([
                'api_key' => 'Enter an API key before testing this provider.',
            ]);
        }

        try {
            $message = $tester->test(
                $validated['provider'],
                $apiKey,
                $validated['base_url'] ?: null,
                $validated['model_name'],
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'connection' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'connection' => 'The connection could not be completed. Check the provider URL and your network connection.',
            ]);
        }

        return back()->with('connection_status', $message);
    }
}
