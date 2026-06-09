# SaaS Boilerplate

Reusable Laravel + Nuxt SaaS starter with tenant-aware authentication, organizations, branches, users, roles, permissions, profile, and neutral dashboard/navigation surfaces.

## Stack

- Backend: Laravel 12, PHP 8.2+, JWT auth, Spatie permissions, organization-scoped tenancy.
- Frontend: Nuxt/Vue/Vuetify. Use Bun for frontend commands.

## Features

### Tenant and organization core

- Organization-first multi-tenant SaaS foundation.
- Organization-scoped data isolation using `organization_id` as the tenant boundary.
- Multi-organization user membership support.
- Active organization context and organization switching.
- Branch/location management under each organization.
- Branch-scoped role assignments for multi-location operations.

### Authentication and users

- JWT-based login and refresh-token flow.
- Authenticated profile endpoint and account context endpoints.
- Demo user seeding for platform admin, organization manager, and member personas.
- User/team management surfaces for organization administrators.
- Neutral profile and account settings surfaces.

### RBAC and permissions

- Spatie Permission-backed RBAC.
- Organization-scoped role definitions.
- Branch/team-scoped role assignment support.
- Seeded neutral SaaS permission catalog for dashboard, organization, branch, users, roles, profile, billing, and audit surfaces.
- Permission-aware navigation and management shell.
- Regression coverage for permission seeder drift, branch-scoped RBAC, and tenant isolation.

### SaaS operations

- Generic dashboard and navigation shell ready for domain-specific modules.
- Organization settings and branch management foundations.
- Billing/subscription surface placeholders for future payment lifecycle implementation.
- Audit/activity-log package included for tenant-aware operational history.
- Media, export, PDF, Firebase, Typesense, Telescope, and ClickHouse packages available for product-specific extension.

### Frontend application

- Nuxt/Vue/Vuetify admin frontend.
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
bun run build
bun run dev
```

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
