<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold text-orange-600">{{ __('Data Management') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $mode === 'create' ? __('Create') : __('Edit') }} {{ $tableLabel }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <div class="mb-6 rounded-xl border border-orange-100 bg-orange-50/70 px-4 py-3 text-sm text-slate-700">
                    {{ __('Required fields are marked with an asterisk. Review values carefully before saving because this updates the imported data table directly.') }}
                </div>
                @if ($errors->any())
                    <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                        <p class="font-semibold">{{ __('Please review the highlighted fields.') }}</p>
                        <ul class="mt-2 list-disc space-y-1 ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ $mode === 'create' ? route('data.table.store', ['table' => $table]) : route('data.table.update', ['table' => $table, 'id' => $row->{$primaryKey}]) }}" class="space-y-5">
                    @csrf
                    @if ($mode === 'edit')
                        @method('PUT')
                    @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        @foreach ($columns as $column)
                            @php
                                $name = $column['name'];
                                $isPrimaryCreate = ($mode === 'create' && $primaryKey === $name);
                                $input = $column['input'];
                                $value = old($name, $row?->{$name});
                                $displayValue = $value;
                                if ($input === 'datetime-local' && is_string($displayValue)) {
                                $displayValue = str_replace(' ', 'T', substr($displayValue, 0, 16));
                            } elseif ($input === 'date' && is_string($displayValue)) {
                                $displayValue = substr($displayValue, 0, 10);
                            }
                                $fieldClass = 'block min-h-11 w-full rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-400';
                            @endphp
                            @if (! $isPrimaryCreate)
                                <div class="{{ $column['full_width'] ? 'md:col-span-2' : '' }}">
                                    <label for="{{ $name }}" class="mb-1 block text-sm font-semibold text-slate-800">
                                        {{ $column['label'] }}
                                        @if ($column['required'])
                                            <span class="required-indicator" aria-hidden="true">*</span>
                                            <span class="sr-only">{{ __('required') }}</span>
                                        @endif
                                    </label>

                                    @if (isset($foreignOptions[$name]) && count($foreignOptions[$name]) > 0)
                                        <select id="{{ $name }}" name="{{ $name }}" class="{{ $fieldClass }}" @required($column['required'])>
                                            <option value="">Select {{ $column['label'] }}</option>
                                            @foreach ($foreignOptions[$name] as $opt)
                                                <option value="{{ $opt['value'] }}" @selected((string) $value === (string) $opt['value'])>{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($input === 'checkbox')
                                        <label for="{{ $name }}" class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                            <input type="hidden" name="{{ $name }}" value="0">
                                            <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" @checked(in_array((string) $value, ['1', 'true', 'on'], true))>
                                            <span class="text-sm text-slate-700">Yes</span>
                                        </label>
                                    @elseif (in_array($input, ['textarea', 'json'], true))
                                        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $column['rows'] }}" class="{{ $fieldClass }} {{ $input === 'json' ? 'font-mono text-sm' : '' }}" placeholder="{{ $column['placeholder'] }}" @required($column['required'])>{{ $value }}</textarea>
                                        @if ($input === 'json')
                                            <p class="form-hint mt-2">{{ __('Enter valid JSON so the record can be saved without formatting errors.') }}</p>
                                        @endif
                                    @elseif ($input === 'datetime-local')
                                        <input id="{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @elseif ($input === 'date')
                                        <input id="{{ $name }}" type="date" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @elseif ($input === 'number')
                                        <input id="{{ $name }}" type="number" step="any" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @else
                                        <input id="{{ $name }}" type="{{ $input }}" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" placeholder="{{ $column['placeholder'] }}" @required($column['required'])>
                                    @endif
                                    <x-input-error :messages="$errors->get($name)" class="mt-2" />
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700" type="submit">{{ $mode === 'create' ? __('Create Record') : __('Update Record') }}</button>
                        <a href="{{ route('data.table.index', ['table' => $table]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-orange-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-orange-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
