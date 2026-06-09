<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Subscription Plans (SaaS Scope)
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('interval')->default('month'); // month, year
            $table->text('description')->nullable();
            $table->json('features')->nullable(); // List of features
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Organization Subscriptions (SaaS Scope)
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained('subscription_plans');

            $table->string('status')->default('active'); // active, past_due, canceled, trial
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // Current period end
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();
        });

        // 3. Transactions (Unified Scope)
        Schema::create('transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained()->nullOnDelete();

            // Payer: Organization (for SaaS) or a generic billable actor.
            $table->ulidMorphs('payer');

            // Product: SubscriptionPlan or another generic billable product.
            $table->ulidMorphs('product'); // product_type, product_id

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            // Type helps filter queries: 'subscription', 'purchase'
            $table->string('type')->index();

            $table->string('status')->default('pending'); // pending, paid, failed, refunded

            $table->string('gateway')->nullable(); // stripe, paypal, manual
            $table->string('gateway_reference')->nullable(); // transaction_id from gateway

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
