<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrganizationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_creation_onboards_default_branch_settings_membership_and_branch_scoped_owner_role(): void
    {
        $this->seed(PermissionSeeder::class);

        $owner = User::factory()->create();

        $organization = (new OrganizationService())->createOrganization([
            'name' => 'Acme Studio',
            'type' => 'studio',
        ], $owner);

        $branch = Branch::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->first();

        $this->assertNotNull($branch);
        $this->assertSame('Main Branch', $branch->name);
        $this->assertTrue((bool) $branch->is_active);

        $this->assertDatabaseHas('configurations', [
            'configurable_type' => $organization->getMorphClass(),
            'configurable_id' => $organization->id,
            'key' => 'billing.currency',
            'value' => 'USD',
        ]);

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_default' => true,
        ]);

        $ownerRole = Role::where('organization_id', $organization->id)
            ->where('name', 'Owner')
            ->firstOrFail();

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $ownerRole->id,
            'model_type' => User::class,
            'model_id' => $owner->id,
            'branch_id' => $branch->id,
        ]);
    }
}
