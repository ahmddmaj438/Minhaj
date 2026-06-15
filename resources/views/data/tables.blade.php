<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">{{ __('Admin workspace') }}</p>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Data Management') }}</h2>
            </div>
            <span class="text-sm text-slate-500">{{ __('Browse and maintain imported TCExam data') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <div class="flex flex-col gap-4 mb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Tables') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Search by table purpose, then open the table to add or edit records.') }}</p>
                    </div>
                    <form method="GET" action="{{ route('data.tables.index') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-[28rem]">
                        <label for="table-search" class="sr-only">{{ __('Search tables') }}</label>
                        <input id="table-search" name="q" value="{{ $search }}" type="search"
                            placeholder="{{ __('Search tables') }}"
                            class="block min-h-11 w-full rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            {{ __('Search') }}
                        </button>
                    </form>
                </div>
                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                    <span>{{ count($tables) }} / {{ $totalTables }} {{ __('tables') }}</span>
                    @if ($search !== '')
                        <a href="{{ route('data.tables.index') }}" class="font-semibold text-orange-700 hover:text-orange-800">{{ __('Clear search') }}</a>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($tables as $table)
                        @php($label = $displayName($table))
                        <a href="{{ route('data.table.index', ['table' => $table]) }}"
                           class="rounded-xl border border-orange-100 p-4 transition hover:bg-orange-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100">
                            <div class="text-base font-semibold text-slate-900">{{ $label }}</div>
                            <div class="mt-2 font-mono text-xs text-slate-500">{{ $table }}</div>
                        </a>
                    @empty
                        <div class="empty-state text-center sm:col-span-2 lg:col-span-3">
                            <strong class="block text-base">{{ __('No tables match your search.') }}</strong>
                            <span class="mt-1 block text-sm">{{ __('Try a shorter keyword or clear the search to view all available tables.') }}</span>
                            @if ($search !== '')
                                <a href="{{ route('data.tables.index') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                    {{ __('Clear search') }}
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
