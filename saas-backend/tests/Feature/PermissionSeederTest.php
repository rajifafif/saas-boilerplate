<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Services\OrganizationService;
use App\Services\PermissionCatalog;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_creates_every_permission_required_by_organization_rbac_initialization(): void
    {
        $this->seed(PermissionSeeder::class);

        $permissions = Permission::query()->pluck('name')->all();
        $requiredPermissions = $this->organizationServicePermissionNames();

        $this->assertNotEmpty($requiredPermissions);
        $this->assertEmpty(array_values(array_diff($requiredPermissions, $permissions)));

        $organization = (new OrganizationService())->createOrganization([
            'name' => 'Seed Drift Guard Studio',
        ], User::factory()->create());

        $this->assertCount(4, $organization->roles()->get());
    }

    private function organizationServicePermissionNames(): array
    {
        return PermissionCatalog::permissions();
    }
}
