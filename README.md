# SaaS Boilerplate

Reusable Laravel + Nuxt SaaS starter with tenant-aware authentication, organizations, branches, users, roles, permissions, profile, and neutral dashboard/navigation surfaces.

## Stack

- Backend: Laravel 12, PHP 8.2+, JWT auth, Spatie permissions, organization-scoped tenancy.
- Frontend: Nuxt/Vue/Vuetify. Use Bun for frontend commands.

## Implemented features

These are the currently implemented and testable boilerplate capabilities.

### Tenant and organization core

- Organization-first multi-tenant SaaS foundation.
- Organization-scoped data isolation using `organization_id` as the tenant boundary.
- Multi-organization user membership support.
- Active organization context and organization switching.
- Branch/location management under each organization.
- Branch-scoped role assignments for multi-location operations.

### Authentication and users

- JWT-based login and refresh-token flow.
- Authenticated profile and current-user context endpoints.
- Demo user seeding for platform administrator, organization manager, and member personas.
- User/team management surfaces for organization administrators.
- Neutral profile and account settings surfaces.

### RBAC and permissions

- Spatie Permission-backed RBAC.
- Organization-scoped role definitions.
- Branch/team-scoped role assignment support.
- Seeded neutral SaaS permission catalog for dashboard, organization, branch, users, roles, profile, billing, and audit surfaces.
- Permission-aware navigation and management shell.
- Regression coverage for permission seeder drift, branch-scoped RBAC, and tenant isolation.

### Frontend application foundation

- Nuxt/Vue/Vuetify admin frontend foundation.
- Public landing, pricing, help center, privacy, login, registration, and password reset surfaces.
- Authenticated dashboard, profile, users/team, roles/permissions, and generic management shell.
- Bun-first frontend workflow with typecheck and production build scripts.
- Legacy business-specific modules removed from the active product surface.

### Developer experience

- Fresh local bootstrap commands for backend and frontend.
- SQLite smoke database option for disposable backend verification.
- Seeded local demo accounts for fast acceptance testing.
- API smoke-test examples for login, profile, current organization, and navigation.
- Backend automated tests covering core SaaS contracts.

## Planned / optional modules

These packages or surfaces are extension points, placeholders, or future modules. Do not treat them as production-ready product flows until the matching design, implementation, and tests exist.

- Billing and subscription lifecycle.
- Midtrans payment creation and webhook handling.
- PDF invoice or receipt generation through DomPDF.
- Media uploads through Spatie Media Library.
- Export/import flows through OpenSpout.
- Search through Scout/Typesense.
- Firebase integrations.
- ClickHouse analytics/reporting.
- Activity log hardening and product-specific audit trails.

## Known limitations

- Payment webhook lifecycle is not implemented yet.
- Subscription activation is currently placeholder/mock until payment lifecycle is designed and tested.
- Some frontend public pages and dialogs may still contain template/demo content.
- Legacy business-specific files may remain temporarily during incremental cleanup, but should not be active user-facing surfaces.
- Frontend typecheck may invoke npm exec for `vue-tsc` unless the dependency/tooling is pinned explicitly.
- Production hardening must be completed before real deployment.

## Fresh local setup

Backend:

```sh
cd saas-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend:

```sh
cd saas-frontend
bun install
bun run build:env:local
bun run typecheck
bun run dev
```

Optional frontend production build:

```sh
cd saas-frontend
bun run build
```

Default local URLs:

- Backend: `http://127.0.0.1:8000`
- Frontend: Nuxt dev default, usually `http://localhost:3000`

For a throwaway backend smoke database, use SQLite:

```sh
cd saas-backend
touch /tmp/saas_boilerplate.sqlite
DB_CONNECTION=sqlite DB_DATABASE=/tmp/saas_boilerplate.sqlite php artisan migrate:fresh --seed
```

## Seeded roles and demo accounts

`php artisan migrate:fresh --seed` runs `DatabaseSeeder`, which calls `SaaSSeeder` and creates:

- Demo Organization: `demo-organization`
- Main Branch: `MAIN`
- Roles: `platform_admin`, `owner`, `organization_manager`, `admin`, `staff`, `member`
- Neutral SaaS permissions for dashboard, organization, branch, users, roles, profile, billing, and audit surfaces

Local-only demo credentials:

| Persona | Email | Password |
| --- | --- | --- |
| Platform Administrator | `platform-admin@demo.com` | `password` |
| Organization Manager | `manager@demo.com` | `password` |
| Member | `member@demo.com` | `password` |

Do not use demo credentials in production.

## API smoke test

Start the backend, then log in:

```sh
curl -s -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"manager@demo.com","password":"password"}'
```

Expected response includes a bearer access token, refresh token, user object, organizations array, and current organization.
Redact tokens in shared logs.

Use the access token as a bearer token to verify these endpoints:

- `GET /api/me`
- `GET /api/organizations/current`
- `GET /api/navigation`

## Test commands

Backend:

```sh
cd saas-backend
php artisan test
```

Frontend:

```sh
cd saas-frontend
bun install --frozen-lockfile
bun run typecheck
```

Optional frontend production build check:

```sh
cd saas-frontend
bun run build
```

Latest known local result from 2026-06-10:

- Backend: `14 passed (117 assertions)`.
- Frontend: `bun run typecheck` exited `0` with npm config warnings only.

## Documentation map

- `docs/BOILERPLATE_CONVENTIONS.md` — tenant, branch, user, staff, customer/member, and RBAC conventions.
- `docs/TENANCY_ARCHITECTURE.md` — tenancy architecture.
- `docs/RBAC_ARCHITECTURE.md` — role and permission architecture.
- `docs/AUTH_ARCHITECTURE.md` — authentication architecture.
- `docs/API_ROUTE_CONVENTIONS.md` — API route naming and compatibility conventions.
- `docs/PACKAGE_DECISIONS.md` — package keep/remove/use-later decisions.
- `docs/PRODUCTION_READINESS.md` — production hardening checklist.
- `docs/FRONTEND_REAL_VS_TEMPLATE.md` — frontend real-vs-template status.
- `docs/PAYMENT_LIFECYCLE.md` — payment lifecycle design/status.
- `docs/PROJECT_IMPROVEMENT_ROADMAP.md` — prioritized frontend/backend improvement plan.
- `docs/QA_REGRESSION_BOILERPLATE_CORE.md` — regression evidence and known gaps.

## Extension guide

- Tenant-owned models should include `organization_id`.
- Branch-specific operational records should include `branch_id` when the business event happens at one location.
- Never trust client-submitted `organization_id` without membership validation.
- Add backend permissions before exposing frontend navigation.
- Add route/controller tests for new API contracts.
- Keep frontend navigation permission-aware.
- Do not reintroduce removed domain-specific modules unless intentionally building that domain.
- Treat installed packages as extension points, not completed features.

## Production readiness

Before real deployment, review `docs/PRODUCTION_READINESS.md`. At minimum:

- Protect or disable Telescope in production.
- Confirm demo credentials cannot exist in production data.
- Configure production-safe auth, cookies, CORS, queues, cache, and session storage.
- Validate tenant isolation and RBAC on every new resource.
- Harden media uploads, audit logging, and third-party payload handling before exposing those features.
- Implement payment signature verification, idempotency, and subscription state tests before enabling billing.

## Manual acceptance smoke checklist

- Platform admin can log in and access platform/organization management.
- Organization manager can log in and see only their organization scope.
- Member can log in and access member-safe dashboard/profile surfaces.
- Branch list/create path is available to manager/admin roles.
- Role/permission management returns neutral organization-scoped roles and permissions.
- Users/team/profile flows work with no removed domain navigation.
- Active API route list and frontend navigation contain no removed business feature entries.

## Domain migration note

This boilerplate intentionally removes active user-facing surfaces for previous business-specific modules such as fitness scheduling and commerce. Some legacy files may remain temporarily during incremental cleanup, but active docs, seeds, navigation, and smoke tests should describe only generic SaaS primitives unless a future task explicitly reintroduces a domain module.
