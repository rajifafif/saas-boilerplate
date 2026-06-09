<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_demo_users_can_login_and_fetch_profile(): void
    {
        $this->seed();

        foreach ([
            'platform-admin@demo.com' => 'Platform Administrator',
            'manager@demo.com' => 'Organization Manager',
            'member@demo.com' => 'Organization Member',
        ] as $email => $name) {
            $login = $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $login->assertOk()
                ->assertJsonPath('user.email', $email)
                ->assertJsonPath('user.name', $name)
                ->assertJsonStructure([
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'user' => ['id', 'email', 'name'],
                    'organizations',
                ]);

            $token = $login->json('access_token');
            $this->assertNotEmpty($token);

            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/me')
                ->assertOk()
                ->assertJsonPath('email', $email)
                ->assertJsonPath('name', $name)
                ->assertJsonMissingPath('member');
        }
    }
}
