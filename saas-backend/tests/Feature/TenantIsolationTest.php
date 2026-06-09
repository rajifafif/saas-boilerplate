<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_read_or_update_another_organizations_branch_through_branch_api(): void
    {
        $this->seed();

        $login = $this->postJson('/api/login', [
            'email' => 'manager@demo.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('access_token');
        $currentOrganizationId = data_get($login->json('organizations.0'), 'id');

        $otherOrganization = Organization::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'type' => 'studio',
        ]);

        $otherBranch = Branch::withoutGlobalScopes()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Private Branch',
            'code' => 'PRIVATE',
            'is_active' => true,
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-ID' => $currentOrganizationId,
        ];

        $this->withHeaders($headers)
            ->getJson('/api/branches/'.$otherBranch->id)
            ->assertNotFound();

        $this->withHeaders($headers)
            ->putJson('/api/branches/'.$otherBranch->id, [
                'name' => 'Leaked Update',
                'code' => 'LEAK',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('branches', [
            'id' => $otherBranch->id,
            'organization_id' => $otherOrganization->id,
            'name' => 'Private Branch',
        ]);
    }
}
