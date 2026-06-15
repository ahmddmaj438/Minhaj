<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
            'availableTables' => Schema::getTableListing(),
            'assignedPermissionNames' => $assignedPermissionNames,
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.groups.insert'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:groups,slug'],
        ]);

        Group::create($data);

        $group = Group::where('slug', $data['slug'])->first();
        $role = Role::firstOrCreate(
            ['slug' => 'group_' . $data['slug']],
            ['name' => $data['name'] . ' Role']
        );
        $group?->roles()->syncWithoutDetaching([$role->id]);

        return redirect()->route('admin.access.index', ['group' => $group?->id])
            ->with('status', 'Group created. Next: configure screens, buttons, DB access, and users.');
    }

    public function updateUserGroups(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'group_ids' => ['array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $user->groups()->sync($data['group_ids'] ?? []);

        return back()->with('status', 'User groups updated.');
    }

    public function updateGroupScreens(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'screens' => ['array'],
            'screens.*' => ['string', Rule::in($this->availableScreenNames())],
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['screens'] ?? [])->map(fn (string $screen) => 'screen.' . $screen . '.view');
        $this->syncPermissionPrefix($role, 'screen.', $selected->all());

        return back()->with('status', 'Group screen access updated.');
    }

    public function updateGroupButtons(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'buttons' => ['array'],
            'buttons.*' => ['string', Rule::in($this->availableButtonKeys())],
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['buttons'] ?? [])->map(fn (string $button) => 'button.' . $button);
        $this->syncPermissionPrefix($role, 'button.', $selected->all());

        return back()->with('status', 'Group button access updated.');
    }

    public function updateGroupDbAccess(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.roles.update'), 403);

        $data = $request->validate([
            'db_permissions' => ['array'],
            'db_permissions.*' => ['string', Rule::in($this->availableDbPermissionKeys())],
        ]);

        $role = $group->roles()->firstOrCreate(
            ['slug' => 'group_' . $group->slug],
            ['name' => $group->name . ' Role']
        );
        $selected = collect($data['db_permissions'] ?? [])->map(fn (string $entry) => 'db.' . $entry);
        $this->syncPermissionPrefix($role, 'db.', $selected->all());

        return back()->with('status', 'Group DB access updated.');
    }

    public function updateGroupUsers(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()?->can('db.group_user.update'), 403);

        $data = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $group->users()->sync($data['user_ids'] ?? []);

        return back()->with('status', 'Group users updated.');
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
            ];
        })->values()->all();
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

        return $base;
    }

    private function availableScreenNames(): array
    {
        return collect($this->availableScreens())->pluck('name')->all();
    }

    private function availableButtonKeys(): array
    {
        return collect($this->availableButtons())
            ->flatMap(fn (array $buttons, string $page) => collect($buttons)
                ->map(fn (string $button): string => $page.'.'.$button))
            ->values()
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
