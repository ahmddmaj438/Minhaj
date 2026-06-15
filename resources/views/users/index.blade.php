<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Users</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Users List</h3>
                        <p class="mt-1 text-sm text-slate-600">Review active accounts and create new users from one place.</p>
                    </div>
                    @can('button.users.create.create_user')
                        <a href="{{ route('users.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            Create User
                        </a>
                    @endcan
                </div>

                <div class="table-comfort overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-slate-700">
                        <thead class="text-xs uppercase bg-slate-100">
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $user->name }}</td>
                                    <td class="px-4 py-2">{{ $user->email }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8">
                                        <div class="empty-state text-center">
                                            <strong class="block text-base">No users yet</strong>
                                            <span class="mt-1 block text-sm">Created users will appear here.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
