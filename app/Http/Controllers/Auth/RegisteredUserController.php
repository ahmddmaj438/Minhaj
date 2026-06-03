<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $defaultGroup = Group::firstOrCreate(
            ['slug' => 'member'],
            ['name' => 'Member']
        );
        $memberRole = Role::firstOrCreate(
            ['slug' => 'member_role'],
            ['name' => 'Member Role']
        );
        $profilePermission = Permission::firstOrCreate(['name' => 'screen.profile.edit.view']);
        $updateProfilePermission = Permission::firstOrCreate(['name' => 'db.users.update']);

        $memberRole->permissions()->syncWithoutDetaching([
            $profilePermission->id,
            $updateProfilePermission->id,
        ]);
        $defaultGroup->roles()->syncWithoutDetaching([$memberRole->id]);
        $user->groups()->syncWithoutDetaching([$defaultGroup->id]);

        event(new Registered($user));

        Auth::login($user);

        if ($user->isStudent()) {
            return redirect(route('student.exams.index', absolute: false));
        }

        if ($user->can('screen.dashboard.view')) {
            return redirect(route('dashboard', absolute: false));
        }

        if ($user->can('screen.profile.edit.view')) {
            return redirect(route('profile.edit', absolute: false));
        }

        return redirect('/');
    }
}
