<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_branch_api_after_fresh_seed(): void
    {
        $this->seed();

        $login = $this->postJson('/api/login', [
            'email' => 'manager@demo.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('access_token');
        $organizationId = data_get($login->json('organizations.0'), 'id');

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($organizationId);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Organization-ID', $organizationId)
            ->getJson('/api/branches')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_access_branch_api_after_fresh_seed(): void
    {
        $this->seed();

        $login = $this->postJson('/api/login', [
            'email' => 'platform-admin@demo.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('access_token');
        $organizationId = data_get($login->json('organizations.0'), 'id');

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($organizationId);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Organization-ID', $organizationId)
            ->getJson('/api/branches')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}

