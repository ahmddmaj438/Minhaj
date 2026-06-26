<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Admin Settings</p>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-950">AI Configuration Test</h2>
        </div>
    </x-slot>

    @php
        $selected = $providers[$selectedProvider] ?? $providers['openai'];
        $configuration = $configurations->get($selectedProvider);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('connection_status'))
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
                    {{ session('connection_status') }}
                </div>
            @endif

            @if ($errors->has('connection'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $errors->first('connection') }}
                </div>
            @endif

            <section class="rounded-xl border border-orange-100 bg-white p-6 shadow-sm">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Provider</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-950">Choose the AI service used by MINHAJ</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Credentials are encrypted before storage. Pollinations Public is available for quick free testing without a bundled key; Gemini is also free-tier-friendly when you add your own key.
                    </p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($providers as $key => $provider)
                        @php
                            $storedProvider = $configurations->get($key);
                            $isSelected = $selectedProvider === $key;
                        @endphp
                        <a href="{{ route('admin.settings.ai-configuration.edit', ['provider' => $key]) }}"
                            class="rounded-xl border p-4 transition {{ $isSelected ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-100' : 'border-slate-200 bg-white hover:border-orange-300' }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-950">{{ $provider['label'] }}</span>
                                @if ($storedProvider?->status === \App\Models\AiConfiguration::STATUS_ACTIVE)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Active</span>
                                @elseif ($storedProvider)
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Saved</span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs leading-5 text-slate-600">{{ $provider['help'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            <form method="POST" action="{{ route('admin.settings.ai-configuration.update') }}"
                x-data="{ showKey: false }"
                class="rounded-xl border border-slate-200 bg-white shadow-sm">
                @csrf
                <input type="hidden" name="provider" value="{{ $selectedProvider }}">

                <div class="border-b border-slate-100 p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Selected provider</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ $selected['label'] }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $selected['help'] }}</p>
                        </div>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $configuration?->api_key ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                            {{ $configuration?->api_key ? 'API key stored securely' : (($selected['api_key_optional'] ?? false) ? 'API key optional' : 'API key required') }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-6 p-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label for="api_key" class="block text-sm font-semibold text-slate-800">API Key</label>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Paste a new key to replace the stored key. Leave this blank to keep the existing encrypted key.
                        </p>
                        <div class="mt-2 flex gap-2">
                            <input id="api_key" name="api_key" :type="showKey ? 'text' : 'password'"
                                value=""
                                autocomplete="new-password"
                                placeholder="{{ $configuration?->api_key ? 'Stored key is hidden' : 'Paste API key' }}"
                                class="block min-w-0 flex-1 rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <button type="button" x-on:click="showKey = ! showKey"
                                x-bind:aria-pressed="showKey"
                                aria-controls="api_key"
                                class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <span x-text="showKey ? 'Hide' : 'Show'">Show</span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
                    </div>

                    <div>
                        <label for="model_name" class="block text-sm font-semibold text-slate-800">Model Name</label>
                        <input id="model_name" name="model_name" required
                            value="{{ old('provider') === $selectedProvider ? old('model_name', $configuration?->model_name ?? $selected['default_model']) : ($configuration?->model_name ?? $selected['default_model']) }}"
                            placeholder="Enter the provider model name"
                            class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <x-input-error :messages="$errors->get('model_name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-800">Status</label>
                        <select id="status" name="status"
                            class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="active" @selected(old('status', $configuration?->status ?? 'inactive') === 'active')>Active - use this provider</option>
                            <option value="inactive" @selected(old('status', $configuration?->status ?? 'inactive') === 'inactive')>Inactive - save without using</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="lg:col-span-2">
                        <label for="base_url" class="block text-sm font-semibold text-slate-800">
                            Base URL
                            @if ($selectedProvider !== 'custom')
                                <span class="font-normal text-slate-500">(optional)</span>
                            @endif
                        </label>
                        <input id="base_url" name="base_url" type="url"
                            value="{{ old('provider') === $selectedProvider ? old('base_url', $configuration?->base_url ?? $selected['default_base_url']) : ($configuration?->base_url ?? $selected['default_base_url']) }}"
                            placeholder="https://provider.example.com/v1"
                            class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Keep the default unless your institution uses a proxy, regional endpoint, or custom compatible provider.
                        </p>
                        <x-input-error :messages="$errors->get('base_url')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    @can('button.admin.settings.ai-configuration.test')
                        <button type="submit" formaction="{{ route('admin.settings.ai-configuration.test') }}"
                            class="inline-flex items-center justify-center rounded-md border border-orange-300 bg-white px-5 py-2.5 text-sm font-semibold text-orange-700 shadow-sm hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Test AI Connection
                        </button>
                    @endcan
                    @can('button.admin.settings.ai-configuration.save')
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Save Configuration
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
