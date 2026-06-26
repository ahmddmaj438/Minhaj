<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\FriendlyName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAccessController extends Controller
{
    public function index(): View
    {
        $selectedGroupId = request()->integer('group');
        $selectedGroup = Group::with('roles.permissions', 'users')->find($selectedGroupId);
        $selectedRole = $selectedGroup?->roles->first();
        $assignedPermissionNames = $selectedRole
            ? $selectedRole->permissions->pluck('name')->all()
            : [];

        return view('admin.access.index', [
            'groups' => Group::with('roles.permissions', 'users')->orderBy('name')->get(),
            'users' => User::with('groups')->orderBy('name')->get(),
            'selectedGroup' => $selectedGroup,
            'availableScreens' => $this->availableScreens(),
            'availableButtons' => $this->availableButtons(),
            'availableDataSections' => $this->availableDataSections(),
            'assignedPermissionNames' => $assignedPermissionNames,
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.groups.insert'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:groups,slug'],
        ], [
            'name.required' => 'Please enter a role name.',
            'slug.required' => 'Please enter a short role code.',
            'slug.unique' => 'This role is already registered.',
        ]);

        Group::create($data);

        $group = Group::where('slug', $data['slug'])->first();
        $role = Role::firstOrCreate(
            ['slug' => 'group_' . $data['slug']],
            ['name' => $data['name'] . ' Role']
        );
        $group?->roles()->syncWithoutDetaching([$role->id]);

        return redirect()->route('admin.access.index', ['group' => $group?->id])
            ->with('status', 'User role created. Next: choose pages, allowed actions, data access, and members.');
    }

    public function updateUserGroups(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('db.group_user.update'), 403);

        $data = $request->validate([
            'group_ids' => ['array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ], [
            'group_ids.*.exists' => 'The selected role was not found.',
        ]);

        $user->groups()->sync($data['group_ids'] ?? []);

        return back()->with('status', 'User role assignment was saved.');
    }

    public function destroyGroup(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.groups.delete'), 403);

        if ($group->users()->exists()) {
            return back()->withErrors([
                'role' => 'This role is already assigned. Remove members before removing the role.',
            ]);
        }

        $roles = $group->roles()->withCount(['groups', 'users'])->get();

        $group->users()->detach();
        $group->rules()->detach();
        $group->roles()->detach();
        $group->delete();

        foreach ($roles as $role) {
            if ($role->groups_count <= 1 && $role->users_count === 0) {
                $role->permissions()->detach();
                $role->delete();
            }
        }

        return redirect()->route('admin.access.index')
            ->with('status', 'User role was removed from the system.');
    }

    public function updateGroupScreens(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'screens' => ['array'],
            'screens.*' => ['string', Rule::in($this->availableScreenNames())],
        ], [
            'screens.*.in' => 'The selected page was not found.',
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['screens'] ?? [])->map(fn (string $screen) => 'screen.' . $screen . '.view');
        $this->syncPermissionPrefix($role, 'screen.', $selected->all());

        return back()->with('status', 'Page access was saved.');
    }

    public function updateGroupButtons(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'buttons' => ['array'],
            'buttons.*' => ['string', Rule::in($this->availableButtonKeys())],
        ], [
            'buttons.*.in' => 'The selected action was not found.',
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['buttons'] ?? [])->map(fn (string $button) => 'button.' . $button);
        $this->syncPermissionPrefix($role, 'button.', $selected->all());

        return back()->with('status', 'Action access was saved.');
    }

    public function updateGroupDbAccess(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'db_permissions' => ['array'],
            'db_permissions.*' => ['string', Rule::in($this->availableDbPermissionKeys())],
        ], [
            'db_permissions.*.in' => 'The selected information permission was not found.',
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['db_permissions'] ?? [])->map(fn (string $entry) => 'db.' . $entry);
        $this->syncPermissionPrefix($role, 'db.', $selected->all());

        return back()->with('status', 'Data access was saved.');
    }

    public function updateGroupUsers(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.group_user.update'), 403);

        $data = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'user_ids.*.exists' => 'The selected user was not found.',
        ]);

        $group->users()->sync($data['user_ids'] ?? []);

        return back()->with('status', 'Role members were saved.');
    }

    private function syncPermissionPrefix(Role $role, string $prefix, array $selectedNames): void
    {
        $selectedIds = collect($selectedNames)
            ->unique()
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name])->id)
            ->all();

        $existingPrefixIds = $role->permissions()
            ->where('name', 'like', $prefix . '%')
            ->pluck('permissions.id')
            ->all();

        if (! empty($existingPrefixIds)) {
            $role->permissions()->detach($existingPrefixIds);
        }
        if (! empty($selectedIds)) {
            $role->permissions()->syncWithoutDetaching($selectedIds);
        }
    }

    private function availableScreens(): array
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            $name = $route->getName();
            if (! $name) {
                return false;
            }
            return in_array('GET', $route->methods(), true) && ! str_starts_with($name, 'debugbar.');
        });

        return $routes->map(function ($route) {
            return [
                'name' => $route->getName(),
                'uri' => '/' . ltrim($route->uri(), '/'),
                ...FriendlyName::screen($route->getName()),
            ];
        })->sortBy('label')->values()->all();
    }

    private function availableButtons(): array
    {
        $base = [
            'dashboard' => ['group_management'],
            'groups.index' => ['create_group', 'edit_group', 'delete_group', 'assign_user_to_group'],
            'admin.access.index' => ['save_screens', 'save_buttons', 'save_db_access', 'save_group_users'],
            'profile.edit' => ['update_profile', 'update_password', 'delete_account'],
            'data.table.create' => ['create_record'],
            'data.table.edit' => ['update_record'],
            'data.table.index' => ['delete_record'],
            'instructor.exams.questions.order.index' => ['save', 'duplicate', 'delete'],
        ];

        $routes = collect(Route::getRoutes());
        foreach ($routes as $route) {
            $name = $route->getName();
            if (! $name || str_starts_with($name, 'debugbar.')) {
                continue;
            }

            $methods = collect($route->methods())->reject(fn (string $m) => in_array($m, ['HEAD', 'OPTIONS'], true))->values();
            if ($methods->contains('POST') && str_ends_with($name, '.store')) {
                $resource = explode('.', $name)[0] ?? 'resource';
                $page = str_replace('.store', '.create', $name);
                $button = 'create_' . rtrim($resource, 's');
                $base[$page] = array_values(array_unique(array_merge($base[$page] ?? [], [$button])));
            }
        }

        return collect($base)
            ->map(function (array $buttons, string $page): array {
                return [
                    'page' => $page,
                    ...FriendlyName::screen($page),
                    'buttons' => collect($buttons)
                        ->map(fn (string $button): array => [
                            'key' => $button,
                            'value' => $page.'.'.$button,
                            'label' => FriendlyName::button($page.'.'.$button),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function availableScreenNames(): array
    {
        return collect($this->availableScreens())->pluck('name')->all();
    }

    private function availableButtonKeys(): array
    {
        return collect($this->availableButtons())
            ->flatMap(fn (array $page) => collect($page['buttons'])
                ->map(fn (array $button): string => $button['value']))
            ->values()
            ->all();
    }

    private function availableDataSections(): array
    {
        $actions = ['select', 'insert', 'update', 'delete'];

        return collect(Schema::getTableListing())
            ->map(fn (string $table): array => [
                'table' => $table,
                ...FriendlyName::dataSection($table),
                'actions' => collect($actions)
                    ->map(fn (string $action): array => [
                        'key' => $table.'.'.$action,
                        'name' => 'db.'.$table.'.'.$action,
                        'label' => FriendlyName::dataAction($action),
                    ])
                    ->all(),
            ])
            ->groupBy('group')
            ->sortKeys()
            ->map(fn ($items) => $items->sortBy('label')->values()->all())
            ->all();
    }

    private function availableDbPermissionKeys(): array
    {
        $actions = ['select', 'insert', 'update', 'delete'];

        return collect(Schema::getTableListing())
            ->flatMap(fn (string $table) => collect($actions)
                ->map(fn (string $action): string => $table.'.'.$action))
            ->values()
            ->all();
    }
}
