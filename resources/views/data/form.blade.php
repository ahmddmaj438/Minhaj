<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ $mode === 'create' ? 'Create' : 'Edit' }} {{ $tableLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
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
                                $fieldClass = 'block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400';
                            @endphp
                            @if (! $isPrimaryCreate)
                                <div class="{{ $column['full_width'] ? 'md:col-span-2' : '' }}">
                                    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">
                                        {{ $column['label'] }}
                                        @if ($column['required'])
                                            <span class="text-orange-600">*</span>
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
                                    @elseif ($input === 'datetime-local')
                                        <input id="{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @elseif ($input === 'date')
                                        <input id="{{ $name }}" type="date" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @elseif ($input === 'number')
                                        <input id="{{ $name }}" type="number" step="any" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" @required($column['required'])>
                                    @else
                                        <input id="{{ $name }}" type="{{ $input }}" name="{{ $name }}" value="{{ $displayValue }}" class="{{ $fieldClass }}" placeholder="{{ $column['placeholder'] }}" @required($column['required'])>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex gap-3">
                        <button class="px-4 py-2 rounded bg-orange-500 text-white hover:bg-orange-600" type="submit">{{ $mode === 'create' ? 'Create Record' : 'Update Record' }}</button>
                        <a href="{{ route('data.table.index', ['table' => $table]) }}" class="px-4 py-2 rounded border border-orange-200 text-slate-700 hover:bg-orange-50">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
