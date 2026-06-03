<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Data Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Tables</h3>
                    <span class="text-sm text-slate-500">{{ count($tables) }} tables</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tables as $table)
                        <a href="{{ route('data.table.index', ['table' => $table]) }}"
                           class="rounded-xl border border-orange-100 p-4 hover:bg-orange-50 transition">
                            <div class="text-base font-semibold text-slate-900">{{ $displayName($table) }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
