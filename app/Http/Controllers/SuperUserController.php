<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SuperUserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('grant_super_admin'), 403);

        return view('admin.super-users.index', [
            'users' => User::orderBy('name')->get(),
            'rootEmail' => User::ROOT_SUPER_ADMIN_EMAIL,
        ]);
    }

    public function grant(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('grant_super_admin'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::findOrFail($data['user_id']);

        try {
            DB::transaction(function () use ($target): void {
                $superRole = Role::firstOrCreate(
                    ['slug' => 'super_admin'],
                    ['name' => 'Super Admin']
                );
                $target->roles()->syncWithoutDetaching([$superRole->id]);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'user_id' => 'Failed to grant super admin access. Please try again.',
            ]);
        }

        return back()->with('status', 'Super admin access granted.');
    }

    public function revoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('grant_super_admin'), 403);

        if ($user->isRootSuperAdmin()) {
            throw ValidationException::withMessages([
                'user_id' => 'Root super admin cannot be revoked.',
            ]);
        }

        try {
            DB::transaction(function () use ($user): void {
                $role = Role::where('slug', 'super_admin')->first();
                if ($role) {
                    $user->roles()->detach($role->id);
                }
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'user_id' => 'Failed to revoke super admin access. Please try again.',
            ]);
        }

        return back()->with('status', 'Super admin access revoked.');
    }
}

