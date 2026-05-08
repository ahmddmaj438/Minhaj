<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ $tableLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-orange-100 text-orange-800 p-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('data.tables.index') }}" class="px-3 py-2 rounded border border-orange-200 text-slate-700 hover:bg-orange-50">All Tables</a>
                <a href="{{ route('data.table.create', ['table' => $table]) }}" class="px-3 py-2 rounded bg-orange-500 text-white hover:bg-orange-600">New Record</a>
            </div>

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-4 overflow-x-auto">
                @if (count($primaryKeys) !== 1)
                    <p class="text-sm text-amber-700 mb-3">Edit/Delete disabled because this table has composite/no primary key.</p>
                @endif
                <table class="min-w-full text-sm text-left text-slate-700">
                    <thead class="bg-slate-100 uppercase text-xs">
                        <tr>
                            @foreach ($columns as $column)
                                <th class="px-3 py-2">{{ $columnLabel($column) }}</th>
                            @endforeach
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-orange-50 align-top">
                                @foreach ($columns as $column)
                                    <td class="px-3 py-2">{{ is_scalar($row->{$column}) || $row->{$column} === null ? $row->{$column} : json_encode($row->{$column}) }}</td>
                                @endforeach
                                <td class="px-3 py-2">
                                    @if ($singlePrimaryKey)
                                        <div class="flex gap-2">
                                            <a href="{{ route('data.table.edit', ['table' => $table, 'id' => $row->{$singlePrimaryKey}]) }}" class="px-2 py-1 rounded border border-orange-200 text-xs">Edit</a>
                                            <form method="POST" action="{{ route('data.table.destroy', ['table' => $table, 'id' => $row->{$singlePrimaryKey}]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 rounded bg-red-600 text-white text-xs">Delete</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

