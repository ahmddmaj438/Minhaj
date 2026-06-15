<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">{{ __('Data Management') }}</p>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ $tableLabel }}</h2>
            </div>
            <span class="font-mono text-xs text-slate-500">{{ $table }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    <p class="font-semibold">{{ __('The action could not be completed.') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col gap-3 rounded-xl border border-orange-100 bg-white/95 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-950">{{ __('Showing latest records') }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ __('Limited to the first 100 rows to keep the page fast.') }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('data.tables.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-orange-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">{{ __('All Tables') }}</a>
                    <a href="{{ route('data.table.create', ['table' => $table]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">{{ __('New Record') }}</a>
                </div>
            </div>

            <div class="table-comfort bg-white/95 shadow-sm rounded-xl border border-orange-100 p-4 overflow-x-auto">
                @if (count($primaryKeys) !== 1)
                    <p role="status" class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900">{{ __('Edit/Delete is disabled because this table has a composite or missing primary key.') }}</p>
                @endif
                <table class="min-w-full text-sm text-left text-slate-700">
                    <thead class="bg-slate-100 uppercase text-xs">
                        <tr>
                            @foreach ($columns as $column)
                                <th class="px-3 py-2">{{ $columnLabel($column) }}</th>
                            @endforeach
                            <th class="px-3 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-orange-50 align-top">
                                @foreach ($columns as $column)
                                    <td class="px-3 py-2">{{ is_scalar($row->{$column}) || $row->{$column} === null ? $row->{$column} : json_encode($row->{$column}) }}</td>
                                @endforeach
                                <td class="px-3 py-2">
                                    @if ($singlePrimaryKey)
                                        <div class="flex gap-2">
                                            <a href="{{ route('data.table.edit', ['table' => $table, 'id' => $row->{$singlePrimaryKey}]) }}" class="inline-flex items-center justify-center rounded-lg border border-orange-200 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-orange-50">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('data.table.destroy', ['table' => $table, 'id' => $row->{$singlePrimaryKey}]) }}" onsubmit="return confirm(@js(__('Delete this record?')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-600">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold text-slate-500">{{ __('Not available') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}" class="px-4 py-8">
                                    <div class="empty-state text-center">
                                        <strong class="block text-base">{{ __('No records found') }}</strong>
                                        <span class="mt-1 block text-sm">{{ __('Create a record or check this table again after imported data is available.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
