<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-orange-600">{{ __('Admin workspace') }}</p>
            <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('Access Management') }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ __('Choose what each role can view, manage, and change using clear business names.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (isset($errors) && $errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @can('db.groups.insert')
                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-950 mb-1">{{ __('Add User Role') }}</h3>
                    <p class="mb-4 text-sm text-slate-600">{{ __('Use a clear role name for a team or responsibility area.') }}</p>
                    <form method="POST" action="{{ route('admin.groups.store') }}" class="grid gap-4 md:grid-cols-3">
                        @csrf
                        <div>
                            <x-input-label for="name" :value="__('Role name')" required />
                            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" placeholder="Instructor Team" required />
                        </div>
                        <div>
                            <x-input-label for="slug" :value="__('Short role code')" required />
                            <x-text-input id="slug" name="slug" type="text" class="mt-2 block w-full" placeholder="instructor_team" required />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button class="w-full">{{ __('Add user role') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold mb-3">{{ __('Choose Role To Configure') }}</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($groups as $group)
                        <a href="{{ route('admin.access.index', ['group' => $group->id]) }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold {{ $selectedGroup?->id === $group->id ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-slate-800 border-orange-100 hover:bg-orange-50' }}">
                            {{ $group->name }}
                        </a>
                    @empty
                        <div class="empty-state w-full">
                            <strong class="block text-base">{{ __('No groups yet') }}</strong>
                            <span class="mt-1 block text-sm">{{ __('Add a role first, then access options will appear here.') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($selectedGroup)
                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-1">{{ __('Selected Role') }}</h3>
                            <p class="text-sm text-slate-600">{{ __('Manage access for :role.', ['role' => $selectedGroup->name]) }}</p>
                        </div>
                        @can('db.groups.delete')
                            <form method="POST" action="{{ route('admin.groups.destroy', $selectedGroup) }}" onsubmit="return confirm('{{ __('Remove this role from the system?') }}')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>{{ __('Remove role') }}</x-danger-button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-1">{{ __('Page Access') }}</h3>
                    <p class="mb-4 text-sm text-slate-600">{{ __('Select the pages this role can open.') }}</p>
                    <form method="POST" action="{{ route('admin.groups.screens.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($availableScreens as $screen)
                                @php($perm = 'screen.' . $screen['name'] . '.view')
                                <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                    <input type="checkbox" name="screens[]" value="{{ $screen['name'] }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                    <span>
                                        <span class="block font-semibold">{{ $screen['label'] }}</span>
                                        <span class="block text-xs text-slate-500">{{ $screen['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @can('db.roles.update')
                            <x-primary-button class="mt-3">{{ __('Save page access') }}</x-primary-button>
                        @endcan
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-1">{{ __('Allowed Actions') }}</h3>
                    <p class="mb-4 text-sm text-slate-600">{{ __('Choose the work this role can perform inside each page.') }}</p>
                    <form method="POST" action="{{ route('admin.groups.buttons.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            @foreach ($availableButtons as $page)
                                <div class="rounded-xl border border-orange-100 bg-white p-3">
                                    <div class="font-semibold text-slate-950 mb-1">{{ $page['label'] }}</div>
                                    <p class="mb-2 text-xs text-slate-500">{{ $page['description'] }}</p>
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($page['buttons'] as $button)
                                            @php($perm = 'button.' . $button['value'])
                                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                                <input type="checkbox" name="buttons[]" value="{{ $button['value'] }}" @checked(in_array($perm, $assignedPermissionNames, true))>
                                                <span>{{ $button['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @can('db.roles.update')
                            <x-primary-button class="mt-3">{{ __('Save action access') }}</x-primary-button>
                        @endcan
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-1">{{ __('System Data Access') }}</h3>
                    <p class="mb-4 text-sm text-slate-600">{{ __('Choose which information this role can view, add, modify, or remove.') }}</p>
                    <form method="POST" action="{{ route('admin.groups.db.update', $selectedGroup) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            @foreach ($availableDataSections as $group => $sections)
                                <section class="rounded-xl border border-orange-100 bg-white p-3">
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $group }}</h4>
                                    <div class="mt-3 space-y-3">
                                        @foreach ($sections as $section)
                                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                                <div class="font-semibold text-slate-950">{{ $section['label'] }}</div>
                                                <p class="mt-1 text-xs text-slate-500">{{ $section['description'] }}</p>
                                                <div class="flex flex-wrap gap-3 mt-2 text-sm">
                                                    @foreach ($section['actions'] as $action)
                                                        <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 font-medium text-slate-800 hover:border-orange-200 hover:bg-orange-50">
                                                            <input type="checkbox" name="db_permissions[]" value="{{ $action['key'] }}" @checked(in_array($action['name'], $assignedPermissionNames, true))>
                                                            <span>{{ $action['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                        @can('db.roles.update')
                            <x-primary-button class="mt-3">{{ __('Save data access') }}</x-primary-button>
                        @endcan
                    </form>
                </div>

                <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                    <h3 class="text-lg font-semibold mb-3">{{ __('Role Members') }}</h3>
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
                        @can('db.group_user.update')
                            <x-primary-button class="mt-3">{{ __('Save role members') }}</x-primary-button>
                        @endcan
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
