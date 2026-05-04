<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Authorization Builder</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 text-green-800 p-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Step 1: Create Group</h3>
                <form method="POST" action="{{ route('admin.groups.store') }}" class="grid gap-3 md:grid-cols-3">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" placeholder="Group Name" required />
                    <x-text-input name="slug" type="text" class="block w-full" placeholder="group_slug" required />
                    <x-primary-button>Create Group</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Select Group To Configure</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($groups as $group)
                        <a href="{{ route('admin.access.index', ['group' => $group->id]) }}"
                           class="px-3 py-2 rounded border {{ $selectedGroup?->id === $group->id ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-800' }}">
                            {{ $group->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($selectedGroup)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 2: Screen Access (from system routes)</h3>
                    <form method="POST" action="{{ route('admin.groups.screens.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($availableScreens as $screen)
                                @php($perm = 'screen.' . $screen['name'] . '.view')
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="screens[]" value="{{ $screen['name'] }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                    <span>{{ $screen['name'] }} <span class="text-gray-500">({{ $screen['uri'] }})</span></span>
                                </label>
                            @endforeach
                        </div>
                        <x-primary-button class="mt-3">Save Screen Access</x-primary-button>
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 3: Page Buttons Access</h3>
                    <form method="POST" action="{{ route('admin.groups.buttons.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            @foreach ($availableButtons as $page => $buttons)
                                <div class="border rounded p-3">
                                    <div class="font-medium mb-2">{{ $page }}</div>
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($buttons as $button)
                                            @php($perm = 'button.' . $page . '.' . $button)
                                            <label class="flex items-center gap-2 text-sm">
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

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 4: Database DML Access (from available tables)</h3>
                    <form method="POST" action="{{ route('admin.groups.db.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            @foreach ($availableTables as $table)
                                <div class="border rounded p-3">
                                    <div class="font-medium">{{ $table }}</div>
                                    <div class="flex flex-wrap gap-3 mt-2 text-sm">
                                        @foreach (['select', 'insert', 'update', 'delete'] as $dml)
                                            @php($perm = 'db.' . $table . '.' . $dml)
                                            <label class="flex items-center gap-2">
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

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-3">Step 5: Assign Users To Group</h3>
                    <form method="POST" action="{{ route('admin.groups.users.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($users as $user)
                                <label class="flex items-center gap-2 text-sm">
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

