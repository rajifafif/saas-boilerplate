# SaaS Boilerplate Conversion Plan

Issue: RAJAA-47 — Turn current app into a reusable SaaS boilerplate.
Owner: CTO

## Success condition

The repository becomes a domain-neutral SaaS starter with working login and tenant-aware administration for:

- Platform Administrator
- Organization Manager / Owner
- Organization Member
- Branch management
- Role and permission management
- User/profile/password flows

All fitness/studio/store-specific features are removed from runtime navigation, API routes, seed data, and frontend pages unless retained as generic SaaS infrastructure.

## Current technical baseline

- Backend: Laravel 12, Sanctum/JWT-related auth code, Spatie Permission, Spatie Multitenancy, activity log, Telescope.
- Frontend: Nuxt 4/Vue 3, Vuetify 3, CASL, Pinia, Bun scripts.
- Existing reusable SaaS core:
  - `users`, `organizations`, `organization_users`, `branches`
  - roles/permissions with organization scope
  - profile, password reset, login/register
  - navigation service/API
  - subscription plan and organization subscription models/routes
- Domain-specific code to remove or quarantine:
  - Fitness/class domain: Coach, Course, CourseCategory, Equipment, Level, Schedule, Booking, Timetable, MemberPackage, Package, LimitedCredit.
  - Commerce/store legacy domain: Store, Product, Order, Payment, Voucher, Customer, Material, Addons, Brand, Color, Satuan, Midtrans package purchase flows.
  - UI pages under `pages/member/*`, `pages/classes.vue`, `pages/timetable.vue`, many `pages/manage/*` pages for coaches/classes/packages/bookings/member-credit/member-group.

## Architecture recommendation

Do an incremental prune, not a rewrite.

1. Define the neutral core modules first: auth, tenants, users, branches, roles/permissions, subscriptions, profile, audit/navigation.
2. Remove domain routes/navigation before deleting models/migrations. This gives a fast, verifiable runtime shell and avoids breaking auth while cleaning deeper code.
3. Delete migrations/models/controllers/resources/services/tests for domain modules after the neutral shell builds.
4. Replace docs/seeds/demo content with generic SaaS language and demo accounts.
5. Verify backend routes and frontend navigation contain no domain nouns.

## Keep / remove boundary

| Area | Keep | Remove |
|---|---|---|
| Auth | login, register, refresh, forgot/reset password, profile, password change | Firebase/social auth only if not working/configured generically |
| Tenancy | organizations, organization_users, current tenant, switch tenant | studio/gym wording |
| RBAC | Spatie roles/permissions, organization-scoped roles, CASL frontend permissions | fitness/store permissions |
| Branches | generic branch/location CRUD | equipment/class room coupling |
| SaaS billing | subscription plans, organization subscriptions, generic invoices if tenant billing | member package purchase and Midtrans class-credit flows |
| Frontend | login/register/dashboard/profile/organization/users/roles/branches/settings | coach/course/classes/timetable/package/member-credit/booking/store/order/payment/voucher pages |

## Rollout waves

### Wave 1 — neutral shell and route/nav prune

Goal: app can login and show a clean SaaS admin dashboard without domain menus.

Tasks:
1. Backend: prune API routes to neutral SaaS surface.
2. Backend: update navigation service/seeded navigation to neutral menus.
3. Frontend: remove/redirect domain pages from nav and dashboard links.
4. QA: smoke login, profile, organization switch, roles page, users page.

### Wave 2 — backend domain deletion

Goal: domain models/controllers/migrations no longer participate in app build/runtime.

Tasks:
1. Delete or quarantine fitness/domain controllers/resources/services/models.
2. Delete or archive domain migrations and update fresh migration path.
3. Remove domain permissions from seeders and role setup.
4. Run backend syntax/test/route checks.

### Wave 3 — frontend domain deletion and rebrand

Goal: frontend is a generic SaaS boilerplate, not a gym/studio app.

Tasks:
1. Delete domain pages/components/composables/stores.
2. Rename package/app metadata from Vuexy/membership/studio wording to SaaS Boilerplate where appropriate.
3. Clean landing/help/pricing copy to generic SaaS tenant billing.
4. Run Bun build/typecheck/lint where available.

### Wave 4 — docs, seeds, and final acceptance

Goal: a new developer can install, seed, login, and verify core SaaS behavior.

Tasks:
1. Update README and documentation with install, seed, default roles/accounts, architecture.
2. Add minimal seed data: platform admin, sample organization, org manager, member, branch, roles.
3. Add acceptance smoke checklist and route/nav domain-noun audit.
4. QA performs browser verification.

## Worker task cards

### Task 1
Title: Prune backend API to neutral SaaS surface
Owner profile: backenddeveloper
Context: Backend still exposes many fitness/store routes in `saas-backend/routes/api.php`.
Goal: Only auth, profile, organizations, branches, roles/permissions, users/staff, subscriptions/billing-core, app/navigation, and health/dev-safe routes remain active.
Scope:
- Edit `saas-backend/routes/api.php`.
- Remove/comment active routes for coach, course/classes, equipment, level, schedules, bookings, timetable, member packages/credits, products/store/orders/payments/vouchers/customers/material/addons/brand/color/satuan.
- Keep imports only for active controllers.
Non-scope:
- Do not delete models/migrations in this task.
- Do not change auth token format.
Likely files/areas:
- `saas-backend/routes/api.php`
- route-linked controllers only if needed to keep route list compiling
Dependencies: None.
Acceptance criteria:
- `php artisan route:list` succeeds.
- Route list has no active domain endpoints containing: coach, course, class, equipment, level, schedule, booking, timetable, member-package, credit, product, store, order, payment, voucher, customer, material, addon, brand, color, satuan.
- Login/register/profile/organizations/roles routes still exist.
Verification commands/evidence:
- `cd saas-backend && php artisan route:list`
- `cd saas-backend && php artisan route:list | grep -Ei 'coach|course|class|equipment|level|schedule|booking|timetable|member-package|credit|product|store|order|payment|voucher|customer|material|addon|brand|color|satuan' || true`
- `cd saas-backend && php artisan route:list | grep -Ei 'login|register|profile|organizations|roles'`
Risk/blocker policy:
- If a neutral feature depends on a domain controller, stop and report exact dependency before broad refactor.
Done evidence:
- Changed files, route-list output, grep audit output.

### Task 2
Title: Neutralize backend navigation and permissions
Owner profile: backenddeveloper
Context: Navigation/RBAC likely still includes gym/studio/store modules.
Goal: API navigation returns only SaaS boilerplate menu items and permissions.
Scope:
- Inspect `NavigationService`, navigation seeders/migrations, permission seeders, `RoleService`.
- Keep menus for dashboard, organizations, branches, users/team, roles/permissions, profile/account settings, subscription/billing if generic.
- Remove domain permissions/menus for class/course/coach/equipment/level/member packages/bookings/store/order/payment/voucher/product.
Non-scope:
- Do not implement frontend layout changes.
Likely files/areas:
- `saas-backend/app/Services/NavigationService.php`
- `saas-backend/app/Services/RoleService.php`
- `saas-backend/database/seeders/*`
- navigation/permission migrations if seeded there
Dependencies: Task 1 preferred but can run in parallel if routes known.
Acceptance criteria:
- Authenticated `GET /api/navigation` has no domain nouns in labels, paths, or permission names.
- Seed/fresh migration path creates neutral roles for platform admin, organization manager, member.
Verification commands/evidence:
- `cd saas-backend && php artisan test` or smallest available backend test command.
- Seed/navigation evidence via test, tinker, or curl against local app.
- Text grep audit for removed nouns in navigation/permission seed files.
Risk/blocker policy:
- If seed path is currently broken, report exact failing migration/seeder and fix only if scoped to navigation/RBAC.
Done evidence:
- Changed files, test/seed command output, sample navigation JSON or grep output.

### Task 3
Title: Build neutral frontend shell and remove domain navigation
Owner profile: frontenddeveloper
Context: Nuxt frontend has domain pages and dashboards under `pages/member`, `pages/classes.vue`, `pages/timetable.vue`, and `pages/manage/*`.
Goal: Logged-in users see a neutral SaaS admin/member shell with no gym/studio/store links.
Scope:
- Update frontend navigation consumers and dashboards to use neutral menu labels.
- Keep pages: login, register, forgot/reset password, profile, organizations/account settings, roles, users/team, branches if present.
- Replace member dashboard/classes/timetable links with generic organization/member dashboard content.
- Remove or redirect domain pages from navigation.
Non-scope:
- Do not delete all unused components yet unless necessary for build.
- Do not change API auth contract.
Likely files/areas:
- `saas-frontend/pages/index.vue`
- `saas-frontend/pages/manage/index.vue`
- `saas-frontend/pages/manage/roles/*`
- `saas-frontend/pages/manage/users/*`
- `saas-frontend/components/global/CommandPalette.vue`
- frontend nav/store/composables files discovered by worker
Dependencies: Task 1/2 API contract, or work against existing neutral route expectations.
Acceptance criteria:
- No visible navigation/menu/search result links to coach, class, timetable, package, booking, equipment, level, member credit/group, product/store/order/payment/voucher.
- Login/register/profile pages still render/build.
- Dashboard copy says SaaS/organization/team/branch, not studio/gym/pilates/class.
Verification commands/evidence:
- `cd saas-frontend && bun run typecheck`
- `cd saas-frontend && bun run build`
- Grep audit of frontend source for domain routes/labels, with remaining false positives explained.
- Screenshot/browser evidence if local app can run.
Risk/blocker policy:
- If build is already broken before changes, capture baseline failure first and avoid unrelated fixes.
Done evidence:
- Changed files, build/typecheck output, grep audit, screenshots if available.

### Task 4
Title: Delete backend domain modules after shell passes
Owner profile: backenddeveloper
Context: After routes/nav are neutral, unused domain code should be removed to make the boilerplate maintainable.
Goal: Backend no longer contains active domain models/controllers/services/resources/migrations for removed business features.
Scope:
- Delete/quarantine code for Coach, Course, CourseCategory, Equipment, Level, Schedule, Booking, Timetable, MemberPackage, LimitedCredit, Package, Remark, Store/Product/Order/Payment/Voucher/Customer legacy modules if not generic SaaS billing.
- Update autoload references, policies, factories, seeders, tests.
- Preserve generic billing/subscription/invoice if organization-level SaaS billing still uses it.
Non-scope:
- Do not drop generic tenants/users/roles/branches/profile/subscriptions.
Dependencies: Tasks 1 and 2 complete.
Acceptance criteria:
- `composer dump-autoload` succeeds.
- Backend tests or route list succeeds.
- Fresh migration path contains no removed domain tables.
Verification commands/evidence:
- `cd saas-backend && composer dump-autoload`
- `cd saas-backend && php artisan route:list`
- `cd saas-backend && php artisan test` if configured.
- Migration/model grep audit.
Risk/blocker policy:
- If payment/invoice code is ambiguous, preserve it only if it supports organization subscription billing; otherwise flag for CTO decision.
Done evidence:
- Deleted files list, command output, audit output.

### Task 5
Title: Delete frontend domain pages/components and rebrand copy
Owner profile: frontenddeveloper
Context: Frontend should be a clean SaaS starter, not a membership/fitness app.
Goal: Source tree and user-facing copy are domain-neutral.
Scope:
- Delete unused domain pages/components/composables/stores/assets.
- Rebrand package/app names and static copy to SaaS Boilerplate/RAJAA-neutral wording.
- Keep Vuexy template internals only where generic admin UI assets are still used.
Non-scope:
- No redesign beyond clear neutral copy and removing dead routes.
Dependencies: Task 3 complete.
Acceptance criteria:
- Nuxt pages list no removed domain feature routes.
- `bun run build` succeeds.
- Grep audit has no unexplained user-facing references to gym/studio/pilates/coach/class/course/timetable/package/booking/equipment/level/member credit/store/order/voucher/product.
Verification commands/evidence:
- `cd saas-frontend && bun run typecheck`
- `cd saas-frontend && bun run build`
- Grep audit output with false-positive notes.
Risk/blocker policy:
- Preserve generic dependencies until build passes; remove dependency packages in a separate dependency-cleanup if risky.
Done evidence:
- Deleted files list, build/typecheck output, audit output.

### Task 6
Title: Document install, seed, roles, and acceptance smoke test
Owner profile: backenddeveloper
Context: A boilerplate is only useful if a new developer can run it and login.
Goal: Repo docs and seeds support first-run verification.
Scope:
- Update root README/DOCUMENTATION plus backend/frontend READMEs as needed.
- Add/repair seeders for platform admin, sample organization, organization manager, member, branch, roles/permissions.
- Document default credentials via env-safe local-only values; do not commit real secrets.
- Add smoke checklist for login, org switch, branch, roles, users, profile.
Non-scope:
- No production deploy docs beyond local/staging commands.
Dependencies: Tasks 1-5 mostly complete.
Acceptance criteria:
- Fresh setup steps are explicit.
- Demo login succeeds in local environment after migrate/seed.
- Docs contain no domain-specific business feature docs except a migration note that they were removed.
Verification commands/evidence:
- `cd saas-backend && php artisan migrate:fresh --seed` against local dev DB or sqlite test DB.
- Login curl/browser evidence with demo admin.
- `cd saas-frontend && bun run build` or linked frontend verification from Task 5.
Risk/blocker policy:
- If local DB credentials are unavailable, use sqlite test env or report exact missing env owner/action.
Done evidence:
- Changed docs/seed files, migrate/seed output, login evidence.

### Task 7
Title: Final QA acceptance for SaaS boilerplate
Owner profile: qa
Context: Implementation changes span auth, tenancy, RBAC, routes, and UI.
Goal: Independently verify the boilerplate success condition.
Scope:
- Browser smoke test: login, register or demo login, dashboard, profile, organization switch/current tenant, branch list/create if available, roles list/create/edit, users/team list.
- API smoke test: login, profile, organizations/current, navigation, roles/permissions.
- Domain-removal audit: UI navigation and active API routes have no removed domain features.
Non-scope:
- Do not fix code; file blockers with exact repro.
Dependencies: Tasks 1-6 complete.
Acceptance criteria:
- All core SaaS flows pass or blockers are filed with owner/action.
- No removed domain feature is reachable from UI navigation or active API routes.
Verification commands/evidence:
- Browser screenshots or concise screen evidence for each flow.
- Curl/API outputs redacted for tokens.
- Backend route grep and frontend UI audit notes.
Risk/blocker policy:
- Security issues in auth/RBAC are release blockers; assign SecurityEngineer if role exists.
Done evidence:
- QA report comment with pass/fail matrix, commands, screenshots/notes.

## Technical risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Deleting migrations breaks existing local DB/data | Medium | Treat this as boilerplate reset; document fresh setup. Do not run destructive DB commands without explicit owner approval. |
| Domain code entangled with auth/profile/navigation | High | Prune routes/nav first, then delete code in smaller slices with route-list/build checks. |
| Payment code overlaps generic SaaS billing | Medium | Keep organization subscription billing; remove member package purchases. Escalate ambiguous invoice/payment code. |
| Frontend template has many sample pages | Medium | Remove from nav first, then delete once build proves no imports. |
| RBAC scoping regression | High | Require route/API checks as different role types and SecurityEngineer review if permissions behavior changes materially. |

## Final acceptance checklist

- [ ] Platform admin can login and access tenant/organization management.
- [ ] Organization manager can login and manage only their organization scope.
- [ ] Member can login and access only member-safe profile/dashboard.
- [ ] Branch CRUD exists or branch read/create path is documented.
- [ ] Role/permission management works with organization scoping.
- [ ] Backend active route list has no removed domain feature endpoints.
- [ ] Frontend navigation has no removed domain feature links.
- [ ] Fresh install/seed/login documentation works.
- [ ] Tests/build/typecheck pass or known unrelated failures are documented.
