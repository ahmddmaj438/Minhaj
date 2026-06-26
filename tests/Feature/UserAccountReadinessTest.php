<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_disable_and_enable_a_user_account(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['email' => 'teacher@example.com']);

        $this
            ->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.status.update', $user), [
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $this->assertFalse($user->refresh()->is_active);
        $this->post('/logout');

        $this
            ->from('/login')
            ->post('/login', [
                'email' => 'teacher@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this
            ->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.status.update', $user), [
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_last_active_administrator_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $lastAdmin = $this->administrator();

        $this
            ->actingAs($actor)
            ->from(route('users.index'))
            ->patch(route('users.status.update', $lastAdmin), [
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index', absolute: false))
            ->assertSessionHasErrors('user');

        $this->assertTrue($lastAdmin->refresh()->is_active);
    }

    public function test_root_administrator_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $root = User::factory()->create([
            'email' => User::ROOT_SUPER_ADMIN_EMAIL,
        ]);

        $this
            ->actingAs($actor)
            ->from(route('users.index'))
            ->patch(route('users.status.update', $root), [
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index', absolute: false))
            ->assertSessionHasErrors('user');

        $this->assertTrue($root->refresh()->is_active);
    }

    private function administrator(): User
    {
        $permission = Permission::create(['name' => 'screen.admin.access.index.view']);
        $role = Role::create(['name' => 'Administrator', 'slug' => 'administrator']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
