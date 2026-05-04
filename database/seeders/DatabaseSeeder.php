<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $rootSuper = User::firstOrCreate(
            ['email' => User::ROOT_SUPER_ADMIN_EMAIL],
            [
                'name' => 'Root Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );

        $adminGroup = Group::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $memberGroup = Group::firstOrCreate(['slug' => 'member'], ['name' => 'Member']);

        $permissionNames = [
            'screen.dashboard.view',
            'screen.profile.edit.view',
            'screen.admin.access.index.view',
            'screen.groups.index.view',
            'screen.users.index.view',
            'screen.users.create.view',
            'button.dashboard.group_management',
            'button.users.create.create_user',
            'button.groups.index.create_group',
            'button.groups.index.edit_group',
            'button.groups.index.delete_group',
            'button.groups.index.assign_user_to_group',
            'db.users.update',
            'db.users.delete',
            'db.users.insert',
            'db.groups.insert',
            'db.roles.update',
            'db.group_user.update',
        ];

        $permissionIds = collect($permissionNames)->map(function (string $name) {
            return Permission::firstOrCreate(['name' => $name])->id;
        })->all();

        $adminRole = Role::firstOrCreate(['slug' => 'admin_role'], ['name' => 'Admin Role']);
        $memberRole = Role::firstOrCreate(['slug' => 'member_role'], ['name' => 'Member Role']);
        $superRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin']);

        $adminRole->permissions()->sync($permissionIds);
        $memberRole->permissions()->sync(
            Permission::whereIn('name', [
                'screen.profile.edit.view',
                'db.users.update',
            ])->pluck('id')->all()
        );

        $adminGroup->roles()->syncWithoutDetaching([$adminRole->id]);
        $memberGroup->roles()->syncWithoutDetaching([$memberRole->id]);
        $user->groups()->syncWithoutDetaching([$adminGroup->id]);
        $rootSuper->roles()->syncWithoutDetaching([$superRole->id]);
    }
}
