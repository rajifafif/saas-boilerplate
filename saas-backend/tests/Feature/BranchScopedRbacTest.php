<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationService;
use App\Services\RoleService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchScopedRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_user_can_have_different_permissions_in_different_branches(): void
    {
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $organization = (new OrganizationService())->createOrganization([
            'name' => 'Branch RBAC Studio',
        ], User::factory()->create());

        $branchA = Branch::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->firstOrFail();
        $branchB = Branch::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Second Branch',
            'code' => 'SECOND',
            'is_active' => true,
        ]);

        $organization->users()->attach($user->id, [
            'role' => 'staff',
            'is_default' => false,
            'joined_at' => now(),
        ]);

        $staffRole = Role::where('organization_id', $organization->id)->where('name', 'Staff')->firstOrFail();
        $adminRole = Role::where('organization_id', $organization->id)->where('name', 'Admin')->firstOrFail();

        $roleService = app(RoleService::class);
        $roleService->assignExistingRole($branchA, $user, $staffRole);
        $roleService->assignExistingRole($branchB, $user, $adminRole);

        setPermissionsTeamId($branchA->id);
        $this->assertTrue($user->fresh()->can('member.create'));
        $this->assertFalse($user->fresh()->can('branch.update'));

        setPermissionsTeamId($branchB->id);
        $this->assertTrue($user->fresh()->can('member.create'));
        $this->assertTrue($user->fresh()->can('branch.update'));
    }
}
