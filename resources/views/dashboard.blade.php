<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white/95 overflow-hidden shadow-sm sm:rounded-xl border border-orange-100">
                    <div class="p-6 text-slate-900">
                        {{ __("You're logged in!") }}
                    </div>
                </div>

                @if (auth()->user()->can('screen.groups.index.view') && auth()->user()->can('button.dashboard.group_management'))
                    <div class="bg-white/95 overflow-hidden shadow-sm sm:rounded-xl border border-orange-200">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-slate-900">User Access Management</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Manage user groups and permission rules from one place.
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('groups.index') }}"
                                   class="inline-flex items-center rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">
                                    Group Management
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
