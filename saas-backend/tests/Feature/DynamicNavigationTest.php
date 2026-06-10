<?php

namespace Tests\Feature;

use App\Models\NavigationItem;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_receives_permission_linked_pages_children_and_actions(): void
    {
        $this->seed();

        $token = $this->loginToken('platform-admin@demo.com');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/navigation');

        $response->assertOk()
            ->assertJsonPath('role', 'platform_admin')
            ->assertJsonPath('layout_type', 'admin')
            ->assertJsonPath('navigation.0.type', 'page')
            ->assertJsonStructure([
                'navigation' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'type',
                        'to',
                        'icon',
                        'permission',
                        'children',
                        'actions',
                    ],
                ],
            ]);

        $rolesPage = collect($response->json('navigation'))
            ->flatMap(fn (array $page) => $page['children'] ?? [])
            ->firstWhere('slug', 'roles');

        $this->assertNotNull($rolesPage, 'Platform admin should see Roles & Permissions child page.');
        $this->assertSame('role.view', $rolesPage['permission']);
        $this->assertContains('role.create', collect($rolesPage['actions'])->pluck('permission')->all());
        $this->assertContains('role.update', collect($rolesPage['actions'])->pluck('permission')->all());
        $this->assertContains('role.delete', collect($rolesPage['actions'])->pluck('permission')->all());
    }

    public function test_navigation_hides_pages_and_actions_when_permission_is_missing(): void
    {
        $this->seed();

        $token = $this->loginToken('member@demo.com');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/navigation')
            ->assertOk();

        $allSlugs = $this->flattenSlugs($response->json('navigation'));
        $allActionPermissions = $this->flattenActionPermissions($response->json('navigation'));

        $this->assertContains('dashboard', $allSlugs);
        $this->assertNotContains('roles', $allSlugs);
        $this->assertNotContains('role.create', $allActionPermissions);
        $this->assertNotContains('role.delete', $allActionPermissions);
    }

    public function test_navigation_seed_creates_permissions_referenced_by_pages_and_actions(): void
    {
        $this->seed();

        $referencedPermissions = NavigationItem::query()
            ->whereNotNull('permission_name')
            ->pluck('permission_name')
            ->unique()
            ->values();

        $seededPermissions = Permission::query()->pluck('name');

        $this->assertNotEmpty($referencedPermissions);
        $this->assertEmpty($referencedPermissions->diff($seededPermissions)->values()->all());
    }

    private function loginToken(string $email): string
    {
        return $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    private function flattenSlugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            $slugs[] = $item['slug'];
            $slugs = array_merge($slugs, $this->flattenSlugs($item['children'] ?? []));
        }

        return $slugs;
    }

    private function flattenActionPermissions(array $items): array
    {
        $permissions = [];

        foreach ($items as $item) {
            foreach ($item['actions'] ?? [] as $action) {
                $permissions[] = $action['permission'];
            }

            $permissions = array_merge($permissions, $this->flattenActionPermissions($item['children'] ?? []));
        }

        return $permissions;
    }
}
