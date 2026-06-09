# SaaS Boilerplate Backend

Laravel 12 API backend for a neutral multi-tenant SaaS starter. It provides JWT login, organization switching, branches, users, and organization-scoped roles/permissions.

Domain-specific gym/pilates modules were pruned from the active product surface as part of the boilerplate migration. Some legacy migrations/models may remain temporarily for schema compatibility while the migration plan tracks removal.

## Requirements

- PHP 8.2+
- Composer
- MySQL/MariaDB for normal local development
- SQLite can be used for smoke verification

## Fresh local setup

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

For a throwaway SQLite smoke database:

```sh
touch /tmp/saas_boilerplate.sqlite
DB_CONNECTION=sqlite DB_DATABASE=/tmp/saas_boilerplate.sqlite php artisan migrate:fresh --seed
```

## Seeded demo data

`php artisan db:seed` runs `SaaSSeeder`, which creates:

- Demo Organization (`demo-organization`)
- Main Branch (`MAIN`)
- Global permissions for dashboard, organization, branch, user, role, profile, billing, and audit surfaces
- Organization-scoped roles: `platform_admin`, `owner`, `organization_manager`, `admin`, `staff`, `member`
- Demo users linked to the demo organization

Local-only demo credentials:

| Role | Email | Password |
| --- | --- | --- |
| Platform Administrator | `platform-admin@demo.com` | `password` |
| Organization Manager | `manager@demo.com` | `password` |
| Member | `member@demo.com` | `password` |

These credentials are seed-only local demo data. Do not use them in production.

## Login smoke check

Start the API, then run:

```sh
curl -s -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"manager@demo.com","password":"password"}'
```

Expected response contract includes:

- access token string (redact in shared evidence)
- refresh token string (redact in shared evidence)
- token type `Bearer`
- expiry seconds
- `user` object
- `organizations` array
- `current_organization` object with role and tenant details

Authenticated smoke endpoints:

- `GET /api/me`
- `GET /api/organizations/current`
- `GET /api/navigation`

Pass the redacted login access token as a bearer token when running those checks.

## Manual SaaS smoke checklist

- Login as `platform-admin@demo.com`, `manager@demo.com`, and `member@demo.com`
- Confirm `/api/me` returns the seeded organization membership and role
- Confirm organization switch returns a new token and active branch
- Confirm branch list/management is visible to manager/admin roles
- Confirm roles/permissions endpoint returns neutral SaaS permissions only
- Confirm users/staff/profile flows do not expose removed domain navigation

## Core API contracts

Public:

- `POST /api/login`
- `POST /api/auth/refresh`
- `POST /api/register`
- `GET /api/organizations/by-slug/{slug}`

Authenticated with a bearer token:

- `GET /api/me`
- `POST /api/logout`
- `GET /api/navigation`
- `GET /api/organizations`
- `GET /api/organizations/current`
- `POST /api/organizations/{organization}/switch`
- `GET /api/organizations/{organization}/roles`
- `GET /api/organizations/{organization}/permissions`

## Notes for boilerplate users

- Multi-tenancy is organization-first. Do not trust client-supplied organization scope without verifying membership.
- Roles are scoped by `organization_id`; permissions are seeded globally and assigned to organization roles.
- Keep local demo credentials documented only as development bootstrap helpers.
