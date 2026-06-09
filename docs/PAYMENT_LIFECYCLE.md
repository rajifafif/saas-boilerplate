# Payment / Subscription Lifecycle

## Current implementation

Status: audit-only baseline. The current backend has subscription tables/models, but it does not yet have a safe payment lifecycle.

Current user-visible path:

1. Authenticated client calls `POST /api/saas/subscribe` with `plan_id` and `organization_id`.
2. `SubscriptionController::subscribe()` validates both IDs.
3. Controller immediately marks the organization subscription as active with a mock-payment comment.
4. No gateway invoice/order is created.
5. No transaction row is created for the subscription payment.
6. No webhook route exists for Midtrans/Stripe/payment settlement.

Important current mismatch:

- Migration columns are `organization_subscriptions.plan_id`, `starts_at`, `ends_at`.
- `OrganizationSubscription` model fillable/controller use `subscription_plan_id`, `start_at`, `end_at`.
- Fresh schema cannot safely support the current subscribe implementation without fixing this contract.

## Target safe lifecycle

### 1. Create invoice/payment order

`POST /api/saas/subscribe` should create a pending subscription payment, not activate access immediately.

Expected behavior:

- Resolve tenant from authenticated organization context; do not trust arbitrary `organization_id` without authorization.
- Validate active `SubscriptionPlan`.
- Create a `transactions` row:
  - `type = subscription`
  - `status = pending`
  - `gateway = midtrans` or `manual`
  - `gateway_reference` / order ID unique per payment attempt
  - `metadata` contains provider request/response identifiers only, no secrets or raw sensitive payloads.
- Return checkout/token/payment instructions to client.

### 2. Webhook validation

Payment settlement must come through a public provider webhook endpoint, but only after verification.

Required before production:

- Add explicit route, e.g. `POST /api/webhooks/midtrans` outside JWT middleware.
- Verify Midtrans signature using server key from config/env.
- Reject invalid signatures with 4xx.
- Do not log secrets or full customer payment payloads.
- Persist sanitized webhook event/audit detail for traceability.

### 3. Idempotency and state machine

Webhook processing must be idempotent.

Required rule:

- Duplicate webhook for the same provider order/transaction must not create duplicate subscriptions, extend twice, or double-write paid transactions.

Recommended transaction states:

- `pending` -> `paid`
- `pending` -> `failed`
- `paid` stays `paid` on duplicate settlement
- `failed/refunded` must not silently reactivate without explicit new payment attempt

Recommended constraints:

- Unique gateway/order reference for payment attempts.
- Row lock or transaction around settlement and subscription activation.

### 4. Settlement activation

Only verified successful settlement should activate subscription.

Expected behavior:

- Mark matching transaction as `paid`.
- Upsert organization subscription using schema-consistent columns:
  - `organization_id`
  - `plan_id`
  - `status = active`
  - `starts_at`
  - `ends_at`
- Calculate period from plan interval and current/previous subscription state.
- If duplicate settlement is received, return success/no-op and keep the same activation period.

### 5. Expiry and cancel

Required lifecycle states:

- Expired subscription should no longer pass active scope after `ends_at`.
- Cancel should set `canceled_at` and status without deleting payment history.
- Renewal should create a new pending transaction and activate/extend only after settlement.

### 6. Audit logging

Required audit trail:

- Transaction status changes.
- Subscription activation/cancel/expiry changes.
- Webhook validation failures summarized without secrets.
- Actor/source: user, gateway webhook, or system job.

## Current test baseline added

`tests/Feature/SubscriptionLifecycleTest.php` documents the unsafe current state:

- No Midtrans/webhook route is registered yet.
- Current subscribe endpoint is incompatible with the fresh subscription schema and returns 500 instead of safely activating.
- Controller still contains the mock-payment activation marker.

These tests intentionally prevent accidental belief that settlement is production-safe. Next implementation should replace the negative/schema-mismatch test with passing tests for pending transaction creation, signature validation, idempotent settlement, and one-time activation.

## Production readiness gaps

Block production billing until these are implemented and reviewed:

1. Schema/model/controller column contract fixed.
2. Pending transaction creation before checkout.
3. Midtrans config via env only; no secrets committed.
4. Verified public webhook route.
5. Idempotent settlement processing.
6. Subscription activation only after verified settlement.
7. Focused tests for duplicate webhook and invalid signature.
8. Security review for webhook signature validation and payment metadata logging.
