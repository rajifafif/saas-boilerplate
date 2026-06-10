<?php

namespace Tests\Feature;

use App\Models\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_update_reorder_and_delete_navigation_items(): void
    {
        $this->seed();
        $token = $this->loginToken('platform-admin@demo.com');

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/platform/navigation-items', [
                'type' => 'page',
                'title' => 'Reports',
                'slug' => 'reports',
                'route' => '/manage/reports',
                'icon' => 'tabler-report',
                'permission_name' => 'report.export',
                'sort_order' => 90,
                'is_active' => true,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.title', 'Reports')
            ->assertJsonPath('data.permission_name', 'report.export');

        $id = $create->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/platform/navigation-items/{$id}", [
                'type' => 'page',
                'title' => 'Reports Center',
                'slug' => 'reports',
                'route' => '/manage/reports',
                'icon' => 'tabler-report-analytics',
                'permission_name' => 'report.export',
                'sort_order' => 95,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Reports Center');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/platform/navigation-items/reorder', [
                'items' => [
                    ['id' => $id, 'parent_id' => null, 'sort_order' => 5],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('navigation_items', [
            'id' => $id,
            'sort_order' => 5,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/platform/navigation-items/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('navigation_items', ['id' => $id]);
    }

    public function test_non_platform_admin_cannot_manage_navigation_items(): void
    {
        $this->seed();
        $token = $this->loginToken('manager@demo.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/platform/navigation-items')
            ->assertForbidden();
    }

    public function test_action_must_belong_to_page_and_cannot_have_children(): void
    {
        $this->seed();
        $token = $this->loginToken('platform-admin@demo.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/platform/navigation-items', [
                'type' => 'action',
                'title' => 'Invalid Action',
                'slug' => 'invalid.action',
                'permission_name' => 'role.create',
            ])
            ->assertUnprocessable();

        $action = NavigationItem::query()->where('type', 'action')->firstOrFail();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/platform/navigation-items', [
                'parent_id' => $action->id,
                'type' => 'page',
                'title' => 'Invalid Child Page',
                'slug' => 'invalid-child-page',
                'permission_name' => 'role.view',
            ])
            ->assertUnprocessable();
    }

    public function test_permission_name_must_exist_when_linking_menu_item(): void
    {
        $this->seed();
        $token = $this->loginToken('platform-admin@demo.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/platform/navigation-items', [
                'type' => 'page',
                'title' => 'Bad Permission Page',
                'slug' => 'bad-permission-page',
                'permission_name' => 'missing.permission',
            ])
            ->assertUnprocessable();
    }

    private function loginToken(string $email): string
    {
        return $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }
}
