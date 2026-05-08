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
                                $type = $column['type'];
                                $value = old($name, $row?->{$name});
                            @endphp
                            @if (! $isPrimaryCreate)
                                <div class="{{ str_contains($type, 'text') ? 'md:col-span-2' : '' }}">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $columnLabel($name) }}</label>

                                    @if (isset($foreignOptions[$name]) && count($foreignOptions[$name]) > 0)
                                        <select name="{{ $name }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
                                            <option value="">Select {{ $columnLabel($name) }}</option>
                                            @foreach ($foreignOptions[$name] as $opt)
                                                <option value="{{ $opt['value'] }}" @selected((string) $value === (string) $opt['value'])>{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif (str_contains($type, 'bool') || str_contains($type, 'tinyint'))
                                        <label class="inline-flex items-center gap-2 mt-2">
                                            <input type="hidden" name="{{ $name }}" value="0">
                                            <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" @checked((string)$value === '1')>
                                            <span class="text-sm text-slate-600">Enabled</span>
                                        </label>
                                    @elseif (str_contains($type, 'text'))
                                        <textarea name="{{ $name }}" rows="4" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">{{ $value }}</textarea>
                                    @elseif (str_contains($type, 'date') || str_contains($type, 'time'))
                                        <input type="text" name="{{ $name }}" value="{{ $value }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" placeholder="YYYY-MM-DD HH:MM:SS">
                                    @elseif (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'numeric') || str_contains($type, 'real') || str_contains($type, 'float'))
                                        <input type="number" step="any" name="{{ $name }}" value="{{ $value }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
                                    @else
                                        <input type="text" name="{{ $name }}" value="{{ $value }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
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

