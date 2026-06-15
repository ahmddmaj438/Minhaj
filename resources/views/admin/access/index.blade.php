<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Authorization Builder</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold text-slate-950 mb-1">Step 1: Create Group</h3>
                <p class="mb-4 text-sm text-slate-600">Use a clear name and a stable slug. The slug is used for permission setup and should not change often.</p>
                <form method="POST" action="{{ route('admin.groups.store') }}" class="grid gap-4 md:grid-cols-3">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Group Name" required />
                        <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" placeholder="Instructor Team" required />
                    </div>
                    <div>
                        <x-input-label for="slug" value="Group Slug" required />
                        <x-text-input id="slug" name="slug" type="text" class="mt-2 block w-full" placeholder="instructor_team" required />
                    </div>
                    <div class="flex items-end">
                        <x-primary-button class="w-full">Create Group</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold mb-3">Select Group To Configure</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($groups as $group)
                        <a href="{{ route('admin.access.index', ['group' => $group->id]) }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold {{ $selectedGroup?->id === $group->id ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-slate-800 border-orange-100 hover:bg-orange-50' }}">
                            {{ $group->name }}
                        </a>
                    @empty
                        <div class="empty-state w-full">
                            <strong class="block text-base">No groups yet</strong>
                            <span class="mt-1 block text-sm">Create a group first, then permissions will appear here.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($selectedGroup)
                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 2: Screen Access (from system routes)</h3>
                    <form method="POST" action="{{ route('admin.groups.screens.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($availableScreens as $screen)
                                @php($perm = 'screen.' . $screen['name'] . '.view')
                                <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                    <input type="checkbox" name="screens[]" value="{{ $screen['name'] }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                    <span>{{ $screen['name'] }} <span class="text-slate-500">({{ $screen['uri'] }})</span></span>
                                </label>
                            @endforeach
                        </div>
                        <x-primary-button class="mt-3">Save Screen Access</x-primary-button>
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 3: Page Buttons Access</h3>
                    <form method="POST" action="{{ route('admin.groups.buttons.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            @foreach ($availableButtons as $page => $buttons)
                                <div class="rounded-xl border border-orange-100 bg-white p-3">
                                    <div class="font-semibold text-slate-950 mb-2">{{ $page }}</div>
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($buttons as $button)
                                            @php($perm = 'button.' . $page . '.' . $button)
                                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                                <input type="checkbox" name="buttons[]" value="{{ $page . '.' . $button }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                                <span>{{ $button }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <x-primary-button class="mt-3">Save Button Access</x-primary-button>
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 4: Database DML Access (from available tables)</h3>
                    <form method="POST" action="{{ route('admin.groups.db.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            @foreach ($availableTables as $table)
                                <div class="rounded-xl border border-orange-100 bg-white p-3">
                                    <div class="font-semibold text-slate-950">{{ $table }}</div>
                                    <div class="flex flex-wrap gap-3 mt-2 text-sm">
                                        @foreach (['select', 'insert', 'update', 'delete'] as $dml)
                                            @php($perm = 'db.' . $table . '.' . $dml)
                                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                                <input type="checkbox" name="db_permissions[]" value="{{ $table . '.' . $dml }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                                <span>{{ strtoupper($dml) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <x-primary-button class="mt-3">Save DB Access</x-primary-button>
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 5: Assign Users To Group</h3>
                    <form method="POST" action="{{ route('admin.groups.users.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($users as $user)
                                <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked($selectedGroup->users->contains('id', $user->id))>
                                    <span>{{ $user->name }} ({{ $user->email }})</span>
                                </label>
                            @endforeach
                        </div>
                        <x-primary-button class="mt-3">Save Group Users</x-primary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
