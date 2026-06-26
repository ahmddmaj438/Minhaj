<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles.permissions', 'groups.roles.permissions'])
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'activeUserCount' => $users->where('is_active', true)->count(),
            'disabledUserCount' => $users->where('is_active', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('button.users.create.create_user'), 403, 'No permission for create user button.');
        abort_unless($request->user()?->can('db.users.insert'), 403, 'No permission to insert into users.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('button.users.index.change_status'), 403);
        abort_unless($request->user()?->can('db.users.update'), 403);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $activate = (bool) $data['is_active'];

        if (! $activate) {
            $this->ensureUserCanBeDisabled($request->user(), $user);
        }

        DB::transaction(function () use ($user, $activate): void {
            $user->forceFill(['is_active' => $activate])->save();
        });

        return back()->with('status', $activate
            ? __('User account enabled.')
            : __('User account disabled. The user can no longer sign in.'));
    }

    private function ensureUserCanBeDisabled(?User $actor, User $target): void
    {
        if (! $actor) {
            abort(403);
        }

        if ((int) $actor->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'user' => __('You cannot disable your own account while you are signed in.'),
            ]);
        }

        if ($target->isRootSuperAdmin()) {
            throw ValidationException::withMessages([
                'user' => __('The root administrator account cannot be disabled.'),
            ]);
        }

        if (! $target->hasAdministrativeAccess()) {
            return;
        }

        $activeAdminCount = User::with(['roles.permissions', 'groups.roles.permissions'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $candidate): bool => $candidate->hasAdministrativeAccess())
            ->count();

        if ($activeAdminCount <= 1) {
            throw ValidationException::withMessages([
                'user' => __('At least one active administrator account must remain available.'),
            ]);
        }
    }
}
