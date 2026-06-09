# QA Regression: Boilerplate Core

Issue: cf23d7d8-da74-45ae-bdc2-1a7763616248
Parent: 4e265b3c-1f52-41dc-b904-740ccdb4ed94
Date: 2026-06-10
Agent: Laravel Backend Developer

## Summary

Backend feature regression now passes after the pre-existing dirty workspace `database/factories/UserFactory.php` change was exercised. Frontend typecheck passes. Frontend production build is still running at report creation time and should be checked from process `proc_3bdd2b5fec79` if additional evidence is needed.

## Evidence Matrix

| Flow / area | Result | Evidence | Notes / owner recommendation |
|---|---:|---|---|
| Fresh test DB migrate via Laravel RefreshDatabase | PASS | `php artisan test --testsuite=Feature` completed 11 tests / 83 assertions | Uses sqlite test DB through Laravel test harness. |
| Organization creation/onboarding | PASS | `Tests\\Feature\\OrganizationOnboardingTest` passed | Verifies default branch, org configuration, membership, branch-scoped owner role. |
| Branch-scoped role permission differences | PASS | `Tests\\Feature\\BranchScopedRbacTest` passed | Verifies same user has Staff permissions in one branch and Admin permissions in another. |
| Tenant isolation | PASS | `Tests\\Feature\\TenantIsolationTest` passed | Verifies authenticated user cannot read/update another organization's branch. |
| Permission seeder/RBAC drift | PASS | `Tests\\Feature\\PermissionSeederTest` passed | Verifies permissions referenced by OrganizationService exist and org roles initialize. |
| Login/profile auth smoke | PASS | `Tests\\Feature\\DemoAuthTest` passed | Seeded demo user login/profile flow passes. |
| Branch API smoke | PASS | `Tests\\Feature\\BranchManagementSmokeTest` passed | Manager/admin branch API access after fresh seed passes. |
| Payment webhook idempotency/lifecycle | FAIL / NOT IMPLEMENTED | `Tests\\Feature\\SubscriptionLifecycleTest` passed guard tests showing webhook route is not registered and subscribe controller still has mock activation marker | Backend owner should implement real Midtrans lifecycle, webhook validation, idempotency, and state tests in a follow-up if in scope. |
| Frontend navigation/type safety | PASS | `bun run typecheck` exited 0 | Nuxt typecheck completed; warnings only from npm env/project config and transient vue-tsc install. |
| Frontend production build | PENDING at report write | `bun run build` started as background process `proc_3bdd2b5fec79` | Check process log for final output before claiming production build pass. |

## Commands run

```sh
cd /Users/rajifafif/www/saas-boilerplate/saas-backend
php -v
composer --version
php artisan --version
php artisan test --testsuite=Feature

cd /Users/rajifafif/www/saas-boilerplate/saas-frontend
bun --version
bun run typecheck
bun run build
```

## Exact outputs captured

Backend:

```text
PHP 8.4.16
Composer version 2.9.2
Laravel Framework 12.44.0
php artisan test --testsuite=Feature
Tests: 11 passed (83 assertions)
Duration: 0.80s
```

Frontend typecheck:

```text
bun 1.3.14
$ nuxt typecheck
[sidebase-auth] Selected provider: local. Auth API location is http://localhost:8001/api
[sidebase-auth] nuxt-auth setup done
exit_code: 0
```

## Initial failure observed

The first full backend feature run failed 3 tests with:

```text
SQLSTATE[HY000]: General error: 1 table users has no column named name
```

A dirty workspace change already present in `database/factories/UserFactory.php` removed the invalid `name` attribute. Re-running the focused and full feature suites passed.

## Risks / untested

- Browser-based screenshots were not produced in this backend-agent run.
- Full manual register/create organization, active org switch, branch switch, logout/refresh/401 browser flows were not manually exercised; automated feature coverage verifies the core backend pieces listed above.
- Payment webhook idempotency is not implemented; current tests intentionally document that state.
- Workspace has pre-existing uncommitted files; no git commit was made.
