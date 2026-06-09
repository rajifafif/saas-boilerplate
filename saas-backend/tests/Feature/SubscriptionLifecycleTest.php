<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SubscriptionController;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_midtrans_webhook_route_is_not_registered_yet(): void
    {
        $webhookRoutes = collect(Route::getRoutes())
            ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->filter(fn (string $route) => str_contains(strtolower($route), 'midtrans') || str_contains(strtolower($route), 'webhook'))
            ->values();

        $this->assertSame([], $webhookRoutes->all(), 'No payment webhook route exists yet; settlement cannot be safely accepted.');
    }

    public function test_current_subscribe_path_is_not_compatible_with_fresh_subscription_schema(): void
    {
        $this->seed();

        $login = $this->postJson('/api/login', [
            'email' => 'platform-admin@demo.com',
            'password' => 'password',
        ])->assertOk();

        $organization = Organization::query()->firstOrFail();
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter-test',
            'price' => 100000,
            'currency' => 'IDR',
            'interval' => 'month',
            'description' => 'Test plan',
            'features' => ['billing'],
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $login->json('access_token'))
            ->withHeader('X-Organization-ID', $organization->id)
            ->postJson('/api/saas/subscribe', [
                'plan_id' => $plan->id,
                'organization_id' => $organization->id,
            ])
            ->assertStatus(500);

        $this->assertDatabaseMissing('organization_subscriptions', [
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'organization_id' => $organization->id,
            'product_id' => $plan->id,
            'type' => 'subscription',
            'status' => 'paid',
        ]);
    }

    public function test_subscribe_controller_still_contains_mock_payment_activation_marker(): void
    {
        $source = file_get_contents((new \ReflectionClass(SubscriptionController::class))->getFileName());

        $this->assertStringContainsString('Mocking payment success', $source);
        $this->assertStringContainsString("'status' => 'active'", $source);
    }
}
