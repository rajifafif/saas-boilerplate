# SaaS Boilerplate Known / Suspected Issues

Purpose: capture problems found during quick audit so they can be reviewed later. Some items may be intentional design choices. If intentional, remove or mark as accepted.

This document is intentionally problem-focused. It is meant to feed future AI-agent planning and cleanup work.

Related source-of-truth docs:
- `docs/BOILERPLATE_CONVENTIONS.md`
- `docs/PACKAGE_DECISIONS.md`

Last updated: 2026-06-10

## Current verdict

The boilerplate has useful SaaS bones, but the wiring is not production-ready yet.

Good foundation:
- Laravel API backend with modern packages.
- Organization, branch, staff, role, permission, subscription, transaction concepts exist.
- Nuxt/Vue admin frontend has many screens/components already.
- Multi-tenancy and RBAC concepts are present.

Main problem:
- Critical systems are partially wired and inconsistent: auth, tenant context, RBAC enforcement, Spatie active branch/team handling, frontend template scope, and test coverage. The organization/branch/customer/staff model itself is intended; the risk is incomplete implementation and missing tests.

## Severity legend

- Critical: can cause runtime failure, data leak, auth/RBAC bypass, or broken onboarding.
- High: likely production blocker or recurring source of bugs.
- Medium: maintainability/productivity risk.
- Low: cleanup/documentation issue.

---

## 1. RBAC and permission problems

Severity: Critical

### 1.1 Permission seeder drift

Status: fixed locally, but keep as regression risk.

Problem:
- `OrganizationService::initializeRbac()` used dotted permissions such as `organization.update`, `branch.manage`, `member.view.any`.
- `database/seeders/PermissionSeeder.php` previously generated unrelated flat permissions such as `manage_organizations`, `view_branches`, etc.
- Spatie `givePermissionTo()` fails if permissions do not exist.

Files:
- `saas-backend/app/Services/OrganizationService.php`
- `saas-backend/database/seeders/PermissionSeeder.php`

Future agent task:
- Add a test that extracts/uses all permissions referenced by `initializeRbac()` and verifies the seeder creates them.
- Prevent future drift by centralizing permissions in one class/config.

### 1.2 Missing model relationships

Status: fixed locally, but keep as regression risk.

Problem:
- `OrganizationService` calls `$organization->roles()`.
- `Organization` previously had no `roles()` relationship.
- `Role` had `organization_id` in fillable but no inverse `organization()` relationship.

Files:
- `saas-backend/app/Models/Organization.php`
- `saas-backend/app/Models/Role.php`

Future agent task:
- Add model relationship tests.
- Verify all role-management controllers use relationships consistently.

### 1.3 Hybrid organization/branch RBAC is intended but enforcement is unverified

Severity: Critical

Intended design:
- SaaS owner/platform owns the software and global subscription/module administration.
- A registered business user gets an `Organization` representing their tenant/business.
- An organization can have many `Branches` representing business locations.
- Branches share customer/member data within the same organization.
- The organization/client/owner can have multiple staff users.
- The same staff user can have different roles per branch, for example Staff in Branch A but Manager in Branch B.
- The same global user account/email can be a customer/member of multiple organizations.
- An organization owner can also register as a customer/member in their own organization and in other organizations.

Current RBAC interpretation:
- Tenant boundary = `organizations`.
- Business location/context = `branches`.
- Role definitions belong to organizations via `roles.organization_id`.
- Role assignments are branch-scoped via `model_has_roles.branch_id`.
- Spatie Permission `team_foreign_key` intentionally maps to `branch_id`.
- Therefore, Spatie "team" means branch context, not organization/tenant.

Problem:
- `docs/BOILERPLATE_CONVENTIONS.md` now documents the intended organization/branch/staff/customer model.
- The remaining issue is enforcement: code must consistently implement that convention.
- Developers and AI agents may still incorrectly treat Spatie team as organization if they do not follow the convention doc.
- Customer/member data must be organization-scoped, not branch-scoped, because branches share customers within one organization. This needs schema/controller verification.

Observed bug already fixed locally:
- Manual insert into `model_has_roles` used `team_id` and `$organization->id` instead of `branch_id` and `$branch->id`.
- It also inserted nonexistent `is_active`.

Files:
- `saas-backend/config/permission.php`
- `saas-backend/database/migrations/2024_11_10_020341_create_permission_tables.php`
- `saas-backend/database/migrations/2025_12_23_000002_create_branches_and_refactor_rbac.php`
- `saas-backend/app/Services/OrganizationService.php`

Risk:
- Permission checks may aggregate permissions across wrong organization/branch if active Spatie team context is not set correctly.
- Staff role checks may accidentally use permissions from another branch if `branch_id` is not set as Spatie's active team id.
- Tenant isolation may be confused with branch scoping.

Future agent task:
- Use `docs/BOILERPLATE_CONVENTIONS.md` as the convention source of truth.
- Optionally split the convention doc later into a dedicated `docs/RBAC_ARCHITECTURE.md`.
- Verify middleware sets Spatie's active team id from the active branch, e.g. via Spatie's team-id API such as `setPermissionsTeamId($branchId)` or the correct version-specific equivalent.
- Define behavior when no branch is selected: deny branch-scoped operations, default branch, or org-wide context.
- Centralize role assignment in `RoleService` instead of manual DB inserts.
- Use config-driven team key or Spatie team-aware APIs.
- Add tests for:
  - user with roles in multiple organizations;
  - user with different roles in multiple branches;
  - customer shared across branches in one organization;
  - same global user registered as customer/member in multiple organizations.

### 1.4 Manual writes to Spatie pivot tables

Severity: High

Problem:
- `OrganizationService` manually inserts into `model_has_roles`.
- Manual insert is brittle because it must match Spatie config and migrations exactly.

File:
- `saas-backend/app/Services/OrganizationService.php`

Future agent task:
- Replace raw pivot inserts with a dedicated method/service.
- The service should validate role organization, branch organization, user membership, and config column names.

### 1.5 Permission cache handling unclear

Severity: Medium

Problem:
- After creating roles/permissions dynamically, Spatie permission cache may need reset.
- Current code does not clearly reset permission cache after RBAC initialization.

Future agent task:
- Verify whether `givePermissionTo()` handles cache reset sufficiently in this flow.
- Add explicit cache reset if needed:
  - `app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();`

---

## 2. Multi-tenancy problems

Severity: Critical

### 2.1 Tenant middleware not consistently applied

Problem:
- `TenantMiddleware` exists and sets `organization_id`, `organization_role`, and optional `branch_id` into request attributes/app container/session.
- `BelongsToOrganization` global scope depends on app/session context.
- Most protected routes in `routes/api.php` are only under `jwt` middleware.
- Only `current-tenant` visibly uses `TenantAwareMiddleware`.

Files:
- `saas-backend/routes/api.php`
- `saas-backend/app/Http/Middleware/TenantMiddleware.php`
- `saas-backend/app/Http/Middleware/TenantAwareMiddleware.php`
- `saas-backend/app/Models/Traits/BelongsToOrganization.php`
- `saas-backend/app/Scopes/TenantScope.php`

Risk:
- Tenant-scoped queries may run without tenant context.
- Models may return no rows or cross-tenant rows depending on scope implementation.
- Data leak risk if a controller forgets explicit organization filters.

Future agent task:
- Define exactly which routes are platform-level, organization-level, and branch-level, following `docs/BOILERPLATE_CONVENTIONS.md`.
- Apply tenant middleware to all organization-level and branch-level route groups.
- Require branch context only for branch-scoped operations.
- Add feature tests proving users cannot access another organization's data.

### 2.2 Tenant context source is mixed

Severity: High

Problem:
- Tenant context may come from JWT claims, override headers, subdomain, app container, and session fallback.
- This is flexible but fragile.

Risk:
- Different request paths may resolve different organizations.
- Queue jobs or CLI commands may have no tenant context.
- Session fallback in API can hide missing stateless context bugs.

Future agent task:
- Pick a strict precedence model and document it.
- Prefer stateless request attributes/app container for API.
- Remove session fallback after migration if not needed.
- Test JWT org, header override, subdomain, and missing-org behavior.

### 2.3 Organization ID auto-fill may be unsafe when middleware missing

Severity: High

Problem:
- `BelongsToOrganization` auto-fills `organization_id` from app-bound `organization_id` or session.
- If context is missing, records may be created without organization_id or fail later.

File:
- `saas-backend/app/Models/Traits/BelongsToOrganization.php`

Future agent task:
- Decide whether tenant-bound model creation should fail hard when no organization context exists.
- Add tests for creating tenant-bound records with and without tenant context.

### 2.4 Customer/member organization scope needs verification

Severity: High

Intended convention:
- Customer/member profiles are organization-scoped.
- Branches inside one organization share customer/member data.
- The same global user/account can be a customer/member in multiple organizations.
- Organization owner/staff can also be customer/member in their own or other organizations.

Problem:
- Need to verify the actual schema and controllers model customer/member data this way.
- If customer/member data is branch-scoped, branches may duplicate customer identities incorrectly.
- If customer/member email is globally unique at the customer level, the same person may be blocked from joining multiple organizations.
- If customer/member is represented only by `users`, organization-specific member profile data may leak or collide.

Future agent task:
- Audit customer/member-related models, migrations, controllers, and frontend forms.
- Confirm whether a separate `members`/`customers` table exists or should be added.
- Enforce uniqueness by `(organization_id, user_id)` if one member profile per account per organization is intended.
- Add tests for customer shared across branches and isolated across organizations.

---

## 3. Auth problems

Severity: High

### 3.1 Too many auth concepts mixed

Problem:
Backend dependencies/features include:
- Laravel Sanctum.
- Manual JWT middleware / JWT refresh route.
- Firebase package.
- Google auth.
- Password reset.

Risk:
- Frontend and backend can disagree about login state.
- Refresh and logout behavior may be inconsistent.
- Organization switch may not update active org/branch/role claims consistently.
- Branch switch may not update active Spatie team id / ability rules consistently.

Files:
- `saas-backend/composer.json`
- `saas-backend/routes/api.php`
- `saas-backend/app/Http/Middleware/JwtMiddleware.php`
- `saas-backend/app/Http/Controllers/Api/AuthController.php`
- `saas-backend/app/Http/Controllers/Api/RegisterController.php`

Future agent task:
- Document the intended auth model:
  - Token type.
  - Cookie or header usage.
  - Refresh rules.
  - Logout invalidation.
  - Active organization/branch/role claims.
  - Whether role claims are informational only or authoritative for permission checks.
- Remove unused auth packages/paths or clearly mark them optional.
- Add auth integration tests.

### 3.2 Logout route appears public and too simple

Severity: Medium

Problem:
- `routes/api.php` has `Route::post('/logout', function () { return true; });` outside the JWT group.
- This may not invalidate tokens/cookies/sessions.

File:
- `saas-backend/routes/api.php`

Future agent task:
- Move logout into authenticated group if needed.
- Implement real token/cookie invalidation.
- Ensure frontend clears auth state on logout and 401.

---

## 4. Organization creation / onboarding problems

Severity: High

### 4.1 Organization creation had multiple runtime bugs

Status: partially fixed locally.

Previously observed problems:
- Duplicate `$organization->users()->attach()` call.
- Missing `roles()` relationship.
- Wrong pivot columns for Spatie role assignment.
- Permissions not seeded correctly.

File:
- `saas-backend/app/Services/OrganizationService.php`

Future agent task:
- Add a full organization creation feature test.
- Test rollback on failure.
- Test duplicate slug/name behavior.
- Test owner membership and owner role assignment.
- Test owner can also become customer/member in the same organization if that flow exists.

### 4.2 Slug uniqueness behavior unclear

Severity: Medium

Problem:
- Organization slug is generated with `Str::slug($data['name'])` if not provided.
- Need to verify DB unique constraint and conflict behavior.

Future agent task:
- Check organization migration.
- Add validation and friendly error on duplicate slug.
- Consider suffix generation if intended.

### 4.3 Default branch is minimal

Severity: Low/Medium

Problem:
- Default branch is created with only name and active flag.
- Branch code/contact/address defaults unclear.

Future agent task:
- Decide minimum branch fields.
- Create branch code convention if needed.

---

## 5. API design problems

Severity: Medium/High

### 5.1 Organization/contextual roles route needs convention alignment

Problem:
- Routes are declared inside `Route::prefix('organizations')`, but role resource is not nested under a specific organization parameter.
- This yields routes like `/organizations/roles`, not `/organizations/{organization}/roles`.
- Contextual active-organization routes are allowed by convention, but the route shape should make that intent clear.
- Role scoping may rely entirely on active tenant context.

File:
- `saas-backend/routes/api.php`

Risk:
- Confusing API shape.
- Frontend/agents may assume route is organization-specific but no org is explicit.

Future agent task:
- Decide route style following `docs/BOILERPLATE_CONVENTIONS.md`:
  - Contextual active tenant route: `/roles` or `/organizations/current/roles`.
  - Explicit nested route: `/organizations/{organization}/roles`.
- Prefer names that clearly distinguish platform, organization, and branch context.
- Make all controllers match chosen style.

### 5.2 Public options routes may leak data

Severity: Medium

Problem:
- `/options/roles` and other option routes are public in `routes/api.php`.
- Some options may be safe public data, but roles may not be.

File:
- `saas-backend/routes/api.php`

Future agent task:
- Review every public options endpoint.
- Move sensitive options behind auth/tenant middleware.

### 5.3 Resource naming inconsistency

Severity: Low

Problem:
- Route resource uses `staffs`; common English/API convention is `staff` or `staff-members`.

File:
- `saas-backend/routes/api.php`

Future agent task:
- Keep if already used by frontend.
- Otherwise add compatible alias or rename carefully.

---

## 6. Frontend problems

Severity: Medium/High

### 6.1 Template bloat

Problem:
Frontend contains many generic admin-template components/pages:
- landing reviews
- FAQ/help center
- refer/share dialogs
- two-factor dialog
- pricing blocks
- generic layout/core components

Risk:
- Fake completeness hides broken real flows.
- More files for agents to scan.
- Harder to identify product-owned code vs template/demo code.

Directory:
- `saas-frontend/`

Future agent task:
- Create `docs/FRONTEND_REAL_VS_TEMPLATE.md`.
- Mark pages/components as:
  - real product
  - template kept intentionally
  - candidate for deletion
- Remove or quarantine unused demo pages.

### 6.2 Auth/tenant frontend behavior needs verification

Severity: High

Problem:
Need to verify frontend handles:
- expired token
- refresh failure
- organization switch
- branch switch
- role/permission updates
- 401 cleanup and redirect

Future agent task:
- Find shared API client/composables.
- Ensure 401/Invalid Token clears cookies/local state and redirects to login.
- Ensure active organization/branch changes refresh user abilities/navigation.
- Scope frontend ability cache by organization + branch, not user only.

### 6.3 Navigation and permissions coupling unclear

Severity: Medium

Problem:
Backend has NavigationController/NavigationService and frontend has layout/nav components.
Need to verify whether menus are permission-driven, role-driven, static, or mixed.

Future agent task:
- Document navigation source of truth.
- Add tests or snapshots for role-based menu output.

---

## 7. Subscription/payment problems

Severity: High

### 7.1 Payment lifecycle likely incomplete

Problem:
- Midtrans dependency and subscription/transaction models exist.
- Need to verify full lifecycle:
  - create invoice/payment
  - webhook signature validation
  - idempotent settlement handling
  - activate subscription
  - expire/cancel subscription
  - audit logs

Files likely involved:
- `saas-backend/app/Http/Controllers/Api/SubscriptionController.php`
- `saas-backend/app/Models/Transaction.php`
- `saas-backend/app/Models/OrganizationSubscription.php`
- `saas-backend/app/Models/SubscriptionPlan.php`

Future agent task:
- Audit payment flow end to end.
- Add webhook route if missing.
- Add idempotency keys / transaction state machine.

---

## 8. Testing problems

Severity: Critical

Problem:
- The bugs found so far should have been caught by basic tests.
- Need to verify actual test coverage.

Missing critical tests:
- PermissionSeeder creates all permissions needed by RBAC setup.
- Organization creation creates default branch, settings, roles, owner membership, owner role assignment.
- User cannot access another organization.
- User with roles in two orgs/branches only gets permissions for active organization + active branch context.
- Auth refresh/logout behavior.
- Organization switch updates active tenant context, branch list/default branch, abilities, and navigation.
- Branch switch updates Spatie team/branch context, abilities, and navigation.
- Role CRUD is organization-scoped.

Future agent task:
- Build tests before refactor.
- Prefer feature tests for core flows.

---

## 9. Documentation problems

Severity: Medium

Problem:
`docs/BOILERPLATE_CONVENTIONS.md` now defines the intended organization/branch/staff/customer/RBAC conventions. Remaining docs gaps:
- Auth architecture.
- Detailed tenant middleware architecture.
- Detailed RBAC implementation architecture, if split from conventions.
- API route conventions.
- Real vs template frontend files.
- Payment lifecycle.
- Production readiness checklist.

Future agent task:
Create docs:
- `docs/AUTH_ARCHITECTURE.md`
- `docs/TENANCY_ARCHITECTURE.md`
- `docs/RBAC_ARCHITECTURE.md` (optional split/detail from `docs/BOILERPLATE_CONVENTIONS.md`)
- `docs/API_ROUTE_CONVENTIONS.md`
- `docs/FRONTEND_REAL_VS_TEMPLATE.md`
- `docs/PAYMENT_LIFECYCLE.md`
- `docs/PRODUCTION_READINESS.md`

---

## 10. Package/scope problems

Severity: Medium

### 10.1 Package intent and optional dependency choices need enforcement

Status: package decisions documented in `docs/PACKAGE_DECISIONS.md`.

Problem:
Some backend packages are intentionally part of the boilerplate, while others are optional feature candidates. This is now documented, but code/config should still enforce that optional packages do not break fresh setup and are not wired prematurely.

Intended packages:
- Activitylog: keep for audit trail / who-did-what logging.
- Telescope: keep for local/dev observability and debugging; protect/disable in production unless explicitly secured.
- Media Library: keep for uploads, user/org assets, documents, avatars, and future file handling.
- OpenSpout: keep for import/export Excel/CSV workflows.

Optional packages needing product decision:
- ClickHouse client.
- Firebase.
- Scout.
- Typesense.
- Midtrans.
- Multitenancy.
- DomPDF.

Recommendation summary:
- Midtrans: keep optional but do not hard-wire until billing/subscription flow is ready. Best choice for Indonesia payment gateway support.
- DomPDF: keep optional if invoice/receipt PDFs are required; otherwise delay. It pairs well with Midtrans/subscription invoices.
- Scout + Typesense: choose only if full-text search is important. If yes, use both together: Scout as Laravel abstraction, Typesense as engine. If search is simple, use database search first.
- Firebase: avoid unless there is a clear need for Firebase Auth/FCM/Google ecosystem. Current auth already has JWT/Sanctum concepts, so Firebase adds auth confusion if not explicitly chosen.
- ClickHouse: avoid for the boilerplate core. Add later only for high-volume analytics/event reporting. PostgreSQL/MySQL is enough first.
- Spatie Multitenancy: current app already has custom single-database `organization_id` tenancy via middleware, app container context, `TenantScope`, and `BelongsToOrganization`. Spatie Multitenancy is not required for this convention unless the project deliberately wants package-managed tenant lifecycle, tenant-aware queues/tasks, config switching, or future multi-database tenancy.

File:
- `saas-backend/composer.json`

Future agent task:
- Create `docs/PACKAGE_DECISIONS.md` with package status: intended core, optional enabled, optional deferred, remove candidate.
- Ensure optional packages do not break fresh setup when config/env is missing.
- Keep optional feature code behind clear service boundaries/config flags.
- Do not introduce Firebase auth, ClickHouse analytics, Typesense search, or Midtrans payment logic until the related product flow is explicitly planned and tested.

---

## Recommended AI-agent work plan

Use this sequence. Do not start with big rewrites.

### Phase 1: Stabilize executable core

Goal:
- Make current intended core flows pass tests.

Tasks:
1. Add organization creation tests.
2. Add permission seeder/RBAC tests.
3. Add tenant isolation tests.
4. Fix failures with minimal code changes.

Acceptance:
- Fresh migrate + seed works.
- Register/create organization works.
- Owner can access organization.
- Cross-tenant access is denied.

### Phase 2: Document architecture decisions

Goal:
- Remove ambiguity before wider refactor.

Tasks:
1. Write auth architecture.
2. Write tenancy architecture.
3. Write RBAC architecture.
4. Write API route conventions.

Acceptance:
- Future agents can tell intended vs accidental behavior.

### Phase 3: Refactor central services

Goal:
- Remove fragile duplicated logic.

Tasks:
1. Centralize role assignment.
2. Centralize tenant resolution.
3. Centralize permission declarations.
4. Make organization onboarding idempotent/tested.

Acceptance:
- No raw manual Spatie pivot writes outside one service.
- No duplicated permission lists.

### Phase 4: Frontend cleanup

Goal:
- Make frontend maintainable.

Tasks:
1. Classify real/template/demo files.
2. Remove or quarantine unused template pages.
3. Verify auth/401 handling.
4. Verify org switch refreshes navigation/permissions.

Acceptance:
- Agents and developers can find real app code quickly.

### Phase 5: Payment/subscription hardening

Goal:
- Make commercial SaaS flow safe.

Tasks:
1. Audit Midtrans flow.
2. Add webhook validation.
3. Add idempotency.
4. Add subscription state tests.

Acceptance:
- Payment settlement safely activates subscription once.

---

## Known local fixes already applied

These were fixed during quick audit and should be verified by tests:

1. `PermissionSeeder.php`
   - Updated to generate permissions used by `initializeRbac()`.

2. `Organization.php`
   - Added `roles()` relationship.

3. `Role.php`
   - Added `organization()` relationship.

4. `OrganizationService.php`
   - Removed duplicate organization-user attach.
   - Replaced wrong `team_id` pivot key with `branch_id`.
   - Removed nonexistent `is_active` pivot column.
   - Used default branch ID for Spatie team-scoped role assignment.

---

## Notes for future agents

- Do not assume every listed issue is unintended. Some may be intended but undocumented.
- First classify each issue as:
  - confirmed bug
  - intended behavior but undocumented
  - obsolete/dead code
  - design tradeoff
- Prefer backend-first fixes.
- Add tests before broad refactors.
- Avoid full rewrite unless tests/profiling prove current design is unsalvageable.
- Do not remove frontend template files blindly; classify first.
- Do not change auth strategy without documenting migration path.
- Do not commit unless explicitly asked.
