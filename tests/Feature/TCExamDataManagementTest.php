<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TCExamDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_table_create_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('data.table.create', ['table' => 'tce_modules']))
            ->post(route('data.table.store', ['table' => 'tce_modules']), [
                'module_name' => '',
                'module_enabled' => 1,
                'module_user_id' => 1,
            ])
            ->assertRedirect(route('data.table.create', ['table' => 'tce_modules'], absolute: false))
            ->assertSessionHasErrors('module_name');

        $this->assertDatabaseCount('tce_modules', 0);
    }

    public function test_dynamic_table_create_handles_duplicate_database_constraints_gracefully(): void
    {
        $user = User::factory()->create();
        DB::table('tce_modules')->insert([
            'module_name' => 'Networking',
            'module_enabled' => 1,
            'module_user_id' => 1,
        ]);

        $this
            ->actingAs($user)
            ->from(route('data.table.create', ['table' => 'tce_modules']))
            ->post(route('data.table.store', ['table' => 'tce_modules']), [
                'module_name' => 'Networking',
                'module_enabled' => 1,
                'module_user_id' => 1,
            ])
            ->assertRedirect(route('data.table.create', ['table' => 'tce_modules'], absolute: false))
            ->assertSessionHasErrors('database');

        $this->assertDatabaseCount('tce_modules', 1);
    }

    public function test_dynamic_table_update_requires_existing_row(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('data.table.update', ['table' => 'tce_modules', 'id' => 999]), [
                'module_name' => 'Missing row',
                'module_enabled' => 1,
                'module_user_id' => 1,
            ])
            ->assertNotFound();
    }

    public function test_non_tce_table_cannot_be_accessed_through_dynamic_editor(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('data.table.index', ['table' => 'users']))
            ->assertNotFound();
    }
}
