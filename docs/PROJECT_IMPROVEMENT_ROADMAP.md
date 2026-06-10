# Project Improvement Roadmap

Purpose: prioritized frontend and backend improvement plan for turning this SaaS boilerplate into a cleaner, safer, production-ready starter.

Last updated: 2026-06-10

---

## Executive summary

The project has a solid Laravel 12 + Nuxt foundation for organization-first SaaS: auth, organizations, branches, RBAC, demo seeds, and core regression tests are already present. The next improvements should focus on truth, safety, and reducing template/placeholder risk:

1. Stabilize production-critical backend contracts: auth/session expiry, tenancy, RBAC, and payment placeholders.
2. Converge frontend navigation and session handling around real backend state.
3. Quarantine or remove template/demo UI that can misrepresent product readiness.
4. Add test and documentation coverage for the flows that make the boilerplate safe to extend.
5. Decide which optional packages are core, optional, or removable.

---

## Priority table

| Priority | Track | Improvement | Outcome |
|---|---|---|---|
| P0 | BE | Fix subscription/payment lifecycle or fully disable the subscribe path | No mock activation or unsafe payment state in product routes. |
| P0 | BE | Harden auth refresh/logout/session expiry contracts | Predictable token lifecycle and safe forced logout behavior. |
| P0 | BE | Verify tenancy/RBAC enforcement on every protected route group | No cross-organization or cross-branch leakage. |
| P0 | FE | Add browser-safe session expiry cleanup | Expired/invalid sessions clear tokens and org/branch context reliably. |
| P0 | FE | Use one navigation source, preferably backend `/api/navigation` | No static template nav leakage or permission drift. |
| P1 | FE | Quarantine template/demo routes, dialogs, and pricing/payment UI | Frontend only exposes real or intentionally retained surfaces. |
| P1 | BE | Stabilize API route conventions and error contracts | Frontend can rely on consistent machine-readable API behavior. |
| P1 | BE/FE | Confirm branch switch contract and ability refresh | Branch-scoped permissions/navigation update correctly after branch switch. |
| P1 | DX | Add database/ERD and API docs if project remains a reusable starter | Easier onboarding for human and AI agents. |
| P1 | QA | Add browser/e2e smoke flows for login, org switch, branch switch, 401, forbidden | UI regressions caught beyond backend feature tests. |
| P2 | BE | Decide optional package lifecycle: keep, wire, or remove | Smaller dependency risk and clearer extension points. |
| P2 | FE | Improve build/typecheck reproducibility | No surprise npm exec install or env confusion. |
| P2 | Ops | Add Docker/compose/deploy guide when runtime target is chosen | Cleaner local and staging bootstrap. |

---

## Backend improvement plan

### BE-01: Payment/subscription safety

Current evidence:

- `docs/PAYMENT_LIFECYCLE.md` says the current subscribe path immediately activates with mock payment logic.
- No verified webhook route exists yet.
- Current tests intentionally document payment lifecycle as not production-safe.

Recommended actions:

1. Choose one path:
   - disable/hide `POST /api/saas/subscribe` until implemented, or
   - implement safe pending-payment lifecycle.
2. Fix schema/model/controller naming mismatch:
   - use `plan_id`, `starts_at`, `ends_at` consistently, or migrate columns intentionally.
3. Create pending transaction before checkout.
4. Add Midtrans webhook route with signature verification.
5. Make settlement idempotent using unique gateway/order reference and DB transaction/lock.
6. Activate subscription only after verified settlement.
7. Add tests for:
   - invalid signature rejected,
   - duplicate webhook no-op,
   - paid settlement activates once,
   - failed/refunded state does not activate,
   - unauthenticated/unauthorized subscribe rejected.

Acceptance:

- `php artisan test --filter=SubscriptionLifecycleTest` passes with positive lifecycle tests.
- README no longer needs to call subscription activation placeholder/mock.
- Production readiness checklist payment items can be checked.

---

### BE-02: Auth refresh/logout hardening

Recommended actions:

1. Make logout behavior explicit:
   - authenticated server-side token revocation, or
   - documented client-only token clearing.
2. Verify refresh validates:
   - signature,
   - expiry,
   - refresh token type,
   - user existence,
   - organization membership/current organization access.
3. Ensure tokens/secrets are never logged.
4. Add machine-readable auth error codes:
   - `token_expired`,
   - `refresh_invalid`,
   - `organization_required`,
   - `organization_forbidden`,
   - `branch_forbidden`.
5. Add regression tests for expired/invalid refresh and revoked/deleted user.

Acceptance:

- Backend tests cover refresh success, invalid refresh, deleted user, and organization membership change.
- Frontend can reliably decide when to retry, clear session, or redirect.

---

### BE-03: Tenancy and RBAC route audit

Recommended actions:

1. List all authenticated routes with their required context:
   - platform/global,
   - organization,
   - branch,
   - user account.
2. Confirm every tenant-owned query scopes by active `organization_id`.
3. Confirm branch IDs always belong to active organization.
4. Confirm Spatie active team is set before branch-scoped permission checks.
5. Add or keep tests for:
   - user cannot access another organization,
   - branch from another organization rejected,
   - same user has different permission results by branch,
   - permission catalog drift fails tests.

Acceptance:

- `php artisan test --testsuite=Feature` passes.
- New route groups cannot bypass tenant resolver/middleware.

---

### BE-04: API consistency and documentation

Recommended actions:

1. Decide canonical route style:
   - active context routes such as `/api/branches`, `/api/roles`, or
   - explicit nested routes such as `/api/organizations/{organization}/branches`.
2. Keep compatibility aliases only with clear docs and removal plan.
3. Standardize pagination shape Laravel-style.
4. Standardize validation/forbidden/not-found error shapes.
5. Add OpenAPI or a concise `docs/api/README.md` if external consumers are expected.

Acceptance:

- `docs/API_ROUTE_CONVENTIONS.md` matches actual routes.
- Route tests verify aliases and canonical routes.

---

### BE-05: Optional package decision cleanup

Packages to classify:

- Spatie Multitenancy
- Telescope
- Activitylog
- Media Library
- OpenSpout
- Midtrans
- DomPDF
- Scout/Typesense
- Firebase
- ClickHouse

Recommended actions:

1. For each package, mark: core, dev-only, optional extension, or remove candidate.
2. Before removing Spatie Multitenancy, check:
   - providers/config dependencies,
   - queued jobs/tasks,
   - published config,
   - `TenantFinder` usage,
   - tests depending on it.
3. Add config guards so optional packages do not expose routes/features by accident.

Acceptance:

- `docs/PACKAGE_DECISIONS.md` has final status per package.
- Unused packages are removed only after tests prove no dependency.

---

## Frontend improvement plan

### FE-01: Browser-safe session expiry cleanup

Current evidence:

- `docs/FRONTEND_REAL_VS_TEMPLATE.md` notes `useApi.ts` and `useTokenRefresh.ts` can rely on Nuxt composables in contexts where redirects/cleanup may fail.
- Org/branch cookies may remain after forced logout.

Recommended actions:

1. Add one helper, e.g. `utils/expireSession.ts`, that can run safely in browser context.
2. It should clear:
   - access token cookie,
   - refresh token/secure storage,
   - user data cookie/storage,
   - current organization cookie,
   - current branch cookie,
   - in-memory auth/org stores when available.
3. Redirect with a safe fallback:
   - Nuxt `navigateTo` when context exists,
   - `window.location.assign('/login?reason=session_expired')` fallback on client.
4. Use this helper from `useApi.ts`, `useTokenRefresh.ts`, and any logout/401 path.
5. Add focused unit tests if test harness exists; otherwise add manual QA steps.

Acceptance:

- Invalid refresh clears tokens and org/branch context.
- User lands on login with clear reason.
- Protected pages cannot keep stale organization/branch state.

---

### FE-02: Single navigation source

Current evidence:

- Backend has `/api/navigation`.
- Frontend still has backend navigation composable plus static template navigation filtered by CASL.
- Template demo navigation can drift or leak.

Recommended actions:

1. Choose backend `/api/navigation` as canonical authenticated navigation source.
2. Use `useNavigation()` in authenticated layout/store.
3. Refresh navigation after:
   - login,
   - organization switch,
   - branch switch,
   - role/permission changes.
4. Quarantine static template nav groups:
   - UI elements,
   - forms,
   - charts,
   - others,
   - pricing/payment placeholders.
5. Keep static nav only for intentionally public/demo documentation routes if product owner approves.

Acceptance:

- Authenticated sidebar renders from backend navigation or one clearly documented source.
- User cannot see links without permission.
- Removed domain/template entries do not appear in product nav.

---

### FE-03: Branch switch and ability refresh

Recommended actions:

1. Confirm whether branch switching is local context only or requires backend token/context refresh.
2. If backend supports branch switch endpoint, wire it through store action.
3. If branch changes permissions, refresh:
   - CASL abilities,
   - backend navigation,
   - visible route guards,
   - branch-scoped data tables.
4. Watch `currentBranchId` in permission/ability logic, not only role.
5. Always use `setCurrentBranch()` so cookie and store remain aligned.

Acceptance:

- Switching branch changes API `X-Branch-ID` consistently.
- Branch-scoped menu/permissions update without page reload.
- Cross-organization branch ID is rejected or cleared.

---

### FE-04: Template/demo quarantine

Current candidate areas from `docs/FRONTEND_REAL_VS_TEMPLATE.md`:

- `navigation/vertical/ui-elements.ts`
- `navigation/vertical/forms.ts`
- `navigation/vertical/charts.ts`
- `navigation/vertical/others.ts`
- horizontal equivalents
- pricing/payment dialogs
- create app/share project/refer dialogs
- template help-center content
- template landing/pricing copy
- access-control demo page

Recommended actions:

1. Move demo-only routes behind a dev flag or remove from product nav.
2. Keep useful layout/components, but remove user-facing links to demos.
3. Rewrite privacy/legal/landing copy before launch.
4. Hide pricing/payment UI until backend payment lifecycle is safe.
5. Create a deletion/quarantine checklist before removing shared components.

Acceptance:

- Product navigation contains only real or approved routes.
- Payment/pricing UI cannot imply active subscription purchase until backend is ready.
- Build/typecheck still passes after quarantine.

---

### FE-05: Frontend quality and reproducibility

Recommended actions:

1. Pin or add missing typecheck tooling if `nuxt typecheck` installs `vue-tsc` through npm exec.
2. Confirm `.env.local` and `.env.production` are documented and safe.
3. Add test scripts if missing:
   - component/unit tests for stores/composables,
   - e2e smoke tests for auth/org/branch flows.
4. Keep Bun-first commands.
5. Add CI commands:
   - `bun install --frozen-lockfile`,
   - `bun run typecheck`,
   - `bun run build`.

Acceptance:

- Fresh clone can typecheck without surprise dependency installation.
- CI can run frontend checks consistently.

---

## Cross-cutting QA plan

### Automated backend checks

```sh
cd saas-backend
php artisan test
```

Add focused filters as features are implemented:

```sh
php artisan test --filter=SubscriptionLifecycleTest
php artisan test --filter=TenantIsolationTest
php artisan test --filter=BranchScopedRbacTest
php artisan test --filter=DemoAuthTest
```

### Frontend checks

```sh
cd saas-frontend
bun install --frozen-lockfile
bun run typecheck
bun run build
```

### Manual/browser smoke flows

1. Login as `platform-admin@demo.com`.
2. Login as `manager@demo.com`.
3. Login as `member@demo.com`.
4. Verify dashboard/profile access per role.
5. Switch organization and verify token/org/branch state.
6. Switch branch and verify `X-Branch-ID`, abilities, navigation, and branch data refresh.
7. Force expired/invalid refresh token and verify full session cleanup.
8. Try forbidden page and verify not-authorized route.
9. Confirm no template/demo nav leaks into authenticated product navigation.
10. Confirm pricing/payment UI is hidden or clearly marked placeholder until backend lifecycle is safe.

---

## Suggested execution order

### Sprint 1: Safety first

1. FE-01 browser-safe session expiry cleanup.
2. FE-02 single navigation source.
3. BE-02 auth refresh/logout hardening.
4. BE-03 tenancy/RBAC route audit.

### Sprint 2: Placeholder risk removal

1. BE-01 disable or implement payment/subscription lifecycle.
2. FE-04 quarantine payment/pricing/template demo UI.
3. BE-04 API consistency and error contracts.
4. Cross-browser manual smoke evidence.

### Sprint 3: Starter polish

1. FE-05 reproducible frontend quality.
2. BE-05 optional package decisions.
3. Add API/database docs if missing.
4. Add Docker/compose/deploy guide if needed.
5. Add screenshots/architecture diagrams to README.

---

## AI-agent-ready task cards

### Card 1 — FE session expiry helper

Goal: create one browser-safe session expiry cleanup helper and route all forced logout/invalid refresh paths through it.

Files to inspect:

- `saas-frontend/composables/useApi.ts`
- `saas-frontend/composables/useTokenRefresh.ts`
- `saas-frontend/composables/useSecureStorage.ts`
- `saas-frontend/stores/organizationStore.ts`
- auth-related cookies/plugins

Acceptance:

- Invalid refresh clears auth and org/branch state.
- Redirect works outside normal Nuxt composable context.
- `bun run typecheck` passes.

---

### Card 2 — FE backend navigation source

Goal: make authenticated product navigation use one source, preferably backend `/api/navigation`, and quarantine demo/template nav.

Files to inspect:

- `saas-frontend/composables/useNavigation.ts`
- `saas-frontend/composables/useFilteredNavigation.ts`
- `saas-frontend/navigation/vertical/*`
- authenticated layout/sidebar files
- `saas-frontend/stores/organizationStore.ts`

Acceptance:

- Product nav refreshes after login/org/branch changes.
- No template/demo links appear for seeded users.
- `bun run typecheck` passes.

---

### Card 3 — BE auth refresh contract tests

Goal: harden and document refresh/logout behavior with feature tests.

Files to inspect:

- `saas-backend/routes/api.php`
- auth controllers/middleware
- JWT/token services
- `tests/Feature/*Auth*`

Acceptance:

- Refresh validates user and organization membership.
- Invalid refresh returns machine-readable error.
- Logout behavior is explicit and tested/documented.
- `php artisan test --filter=Auth` and full test suite pass.

---

### Card 4 — BE payment path safe disable or lifecycle implementation

Goal: remove unsafe mock subscription activation from reachable product behavior or replace with safe pending-payment lifecycle.

Files to inspect:

- `saas-backend/app/Http/Controllers/*Subscription*`
- subscription/payment models and migrations
- `saas-backend/routes/api.php`
- `saas-backend/tests/Feature/SubscriptionLifecycleTest.php`
- `docs/PAYMENT_LIFECYCLE.md`

Acceptance if disabling:

- Subscribe route returns 501/disabled feature response or is hidden.
- Frontend pricing/payment UI cannot call unsafe activation.
- Tests document disabled state.

Acceptance if implementing:

- Pending transaction created before checkout.
- Midtrans signature validation exists.
- Duplicate webhook idempotent.
- Settlement activates exactly once.
- Tests pass.

---

### Card 5 — FE template quarantine

Goal: remove or gate template/demo routes and dialogs from active product surface.

Files to inspect:

- `docs/FRONTEND_REAL_VS_TEMPLATE.md`
- `saas-frontend/pages/front-pages/*`
- `saas-frontend/components/dialogs/*Payment*`
- `saas-frontend/navigation/**`
- `saas-frontend/components/global/CommandPalette.vue`

Acceptance:

- Demo/template areas are inaccessible from product navigation unless intentionally approved.
- Payment/pricing placeholders are hidden or explicitly marked non-production.
- `bun run typecheck` and `bun run build` pass.

---

## Definition of done for the improvement wave

- Backend full test suite passes.
- Frontend typecheck and production build pass.
- Payment/subscription can no longer be mistaken for production-ready unless implemented safely.
- Auth expiry cleanup works from browser behavior, not only composable context.
- Navigation has one authoritative source and no template leakage.
- Tenant/RBAC rules are tested around org and branch boundaries.
- README and docs accurately distinguish implemented, planned, optional, and placeholder features.
