<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.testing_bypass_permissions' => false]);
    }

    public function test_admin_can_access_authorized_pages_and_menu_items(): void
    {
        $admin = $this->userWithPermissions('Admin', [
            'screen.dashboard.view',
            'screen.admin.access.index.view',
            'screen.users.index.view',
            'screen.admin.settings.ai-configuration.edit.view',
            'screen.instructor.exams.create.view',
            'screen.instructor.grading.index.view',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Access')
            ->assertSee('Users')
            ->assertSee('AI Configuration')
            ->assertSee('Exam Builder')
            ->assertSee('Grading');

        $this
            ->actingAs($admin)
            ->get(route('admin.access.index'))
            ->assertOk()
            ->assertSee('Access Management');
    }

    public function test_teacher_sees_teacher_modules_only_and_direct_admin_url_is_forbidden(): void
    {
        $teacher = $this->userWithPermissions('Teacher', [
            'screen.instructor.exams.create.view',
            'screen.instructor.grading.index.view',
        ]);

        $this
            ->actingAs($teacher)
            ->get(route('instructor.exams.create'))
            ->assertOk()
            ->assertSee('Exam Builder')
            ->assertSee('Grading')
            ->assertDontSee('Access')
            ->assertDontSee('Users')
            ->assertDontSee('System Data')
            ->assertDontSee('AI Configuration');

        $this
            ->actingAs($teacher)
            ->get(route('admin.access.index'))
            ->assertForbidden()
            ->assertSee('You do not have permission to access this page.')
            ->assertDontSee('403 Forbidden');
    }

    public function test_student_sees_student_portal_only_and_teacher_page_is_forbidden(): void
    {
        $student = User::factory()->create(['name' => 'Student User']);
        StudentProfile::create([
            'user_id' => $student->id,
            'student_number' => 'STU-100',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);

        $this
            ->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('My Exams')
            ->assertDontSee('Access')
            ->assertDontSee('Users')
            ->assertDontSee('Exam Builder')
            ->assertDontSee('AI Configuration');

        $this
            ->actingAs($student)
            ->get(route('instructor.exams.create'))
            ->assertForbidden()
            ->assertSee('You do not have permission to access this page.');
    }

    public function test_access_page_hides_save_buttons_without_action_permissions(): void
    {
        $viewer = $this->userWithPermissions('Access Viewer', [
            'screen.admin.access.index.view',
        ]);
        $group = Group::create(['name' => 'Teachers', 'slug' => 'teachers']);

        $this->actingAs($viewer);

        $html = view('admin.access.index', [
            'groups' => collect([$group]),
            'users' => collect([$viewer]),
            'selectedGroup' => $group->load('roles.permissions', 'users'),
            'availableScreens' => [
                ['name' => 'dashboard', 'label' => 'Dashboard', 'description' => 'View overview.'],
            ],
            'availableButtons' => [
                [
                    'page' => 'dashboard',
                    'label' => 'Dashboard',
                    'description' => 'View overview.',
                    'buttons' => [
                        ['key' => 'group_management', 'value' => 'dashboard.group_management', 'label' => 'Manage access'],
                    ],
                ],
            ],
            'availableDataSections' => [
                'User and Access Data' => [
                    [
                        'label' => 'User Accounts',
                        'description' => 'Manage people who can use the system.',
                        'actions' => [
                            ['key' => 'users.select', 'name' => 'db.users.select', 'label' => 'View information'],
                        ],
                    ],
                ],
            ],
            'assignedPermissionNames' => [],
        ])->render();

        $this->assertStringContainsString('Page Access', $html);
        $this->assertStringNotContainsString('Add user role', $html);
        $this->assertStringNotContainsString('Save page access', $html);
        $this->assertStringNotContainsString('Save action access', $html);
        $this->assertStringNotContainsString('Save data access', $html);
        $this->assertStringNotContainsString('Save role members', $html);
        $this->assertStringNotContainsString('Remove role', $html);
    }

    public function test_action_permission_is_required_for_permission_updates(): void
    {
        $viewer = $this->userWithPermissions('Access Viewer', [
            'screen.admin.access.index.view',
        ]);
        $group = Group::create(['name' => 'Teachers', 'slug' => 'teachers']);

        $this
            ->actingAs($viewer)
            ->put(route('admin.groups.screens.update', $group), [
                'screens' => ['dashboard'],
            ])
            ->assertForbidden()
            ->assertSee('You do not have permission to perform this action.');
    }

    public function test_role_assignment_and_permission_update_work(): void
    {
        $admin = $this->userWithPermissions('Access Admin', [
            'screen.admin.access.index.view',
            'db.roles.update',
            'db.group_user.update',
        ]);
        $teacher = User::factory()->create();
        $group = Group::create(['name' => 'Teachers', 'slug' => 'teachers']);

        $this
            ->actingAs($admin)
            ->put(route('admin.groups.screens.update', $group), [
                'screens' => ['instructor.exams.create'],
            ])
            ->assertRedirect();

        $this->assertTrue(
            $group->roles()->firstOrFail()->permissions()->where('name', 'screen.instructor.exams.create.view')->exists()
        );

        $this
            ->actingAs($admin)
            ->put(route('admin.groups.users.update', $group), [
                'user_ids' => [$teacher->id],
            ])
            ->assertRedirect();

        $this->assertTrue($group->users()->whereKey($teacher->id)->exists());
    }

    public function test_role_removal_requires_permission_and_blocks_assigned_roles(): void
    {
        $viewer = $this->userWithPermissions('Access Viewer', [
            'screen.admin.access.index.view',
        ]);
        $admin = $this->userWithPermissions('Access Admin', [
            'screen.admin.access.index.view',
            'db.groups.delete',
        ]);
        $teacher = User::factory()->create();
        $assignedGroup = Group::create(['name' => 'Assigned Teachers', 'slug' => 'assigned_teachers']);
        $assignedGroup->users()->sync([$teacher->id]);
        $unusedGroup = Group::create(['name' => 'Unused Role', 'slug' => 'unused_role']);
        $role = Role::create(['name' => 'Unused Role Access', 'slug' => 'unused_role_access']);
        $permission = Permission::create(['name' => 'screen.dashboard.view']);
        $role->permissions()->sync([$permission->id]);
        $unusedGroup->roles()->sync([$role->id]);

        $this
            ->actingAs($viewer)
            ->delete(route('admin.groups.destroy', $unusedGroup))
            ->assertForbidden()
            ->assertSee('You do not have permission to perform this action.');

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index', ['group' => $assignedGroup->id]))
            ->delete(route('admin.groups.destroy', $assignedGroup))
            ->assertRedirect(route('admin.access.index', ['group' => $assignedGroup->id], absolute: false))
            ->assertSessionHasErrors(['role' => 'This role is already assigned. Remove members before removing the role.']);

        $this->assertDatabaseHas('groups', ['id' => $assignedGroup->id]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.groups.destroy', $unusedGroup))
            ->assertRedirect(route('admin.access.index', absolute: false));

        $this->assertDatabaseMissing('groups', ['id' => $unusedGroup->id]);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_access_group_validation_uses_friendly_messages(): void
    {
        $admin = $this->userWithPermissions('Access Admin', [
            'screen.admin.access.index.view',
            'db.groups.insert',
            'db.group_user.update',
        ]);
        Group::create(['name' => 'Teachers', 'slug' => 'teachers']);

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index'))
            ->post(route('admin.groups.store'), [
                'name' => '',
                'slug' => 'teachers',
            ])
            ->assertRedirect(route('admin.access.index', absolute: false))
            ->assertSessionHasErrors([
                'name' => 'Please enter a role name.',
                'slug' => 'This role is already registered.',
            ]);

        $group = Group::firstOrFail();

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index', ['group' => $group->id]))
            ->put(route('admin.groups.users.update', $group), [
                'user_ids' => [99999],
            ])
            ->assertRedirect(route('admin.access.index', ['group' => $group->id], absolute: false))
            ->assertSessionHasErrors(['user_ids.0' => 'The selected user was not found.']);
    }

    private function userWithPermissions(string $groupName, array $permissions): User
    {
        $user = User::factory()->create();
        $slug = str($groupName)->slug('_')->toString();
        $group = Group::create(['name' => $groupName, 'slug' => $slug]);
        $role = Role::create(['name' => $groupName.' Role', 'slug' => $slug.'_role']);

        $role->permissions()->sync(
            collect($permissions)
                ->map(fn (string $permission): int => Permission::firstOrCreate(['name' => $permission])->id)
                ->all()
        );

        $group->roles()->sync([$role->id]);
        $user->groups()->sync([$group->id]);

        return $user;
    }
}
