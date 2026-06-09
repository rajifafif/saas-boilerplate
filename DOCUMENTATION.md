# SaaS Boilerplate Technical Documentation

## Product persona

This repository is a reusable SaaS application starter. It keeps the generic pieces most SaaS products need and removes active product surfaces for previous domain-specific gym/pilates features.

Core personas:

- Platform Administrator: global operator who can inspect and manage organizations.
- Organization Manager: tenant admin who manages one organization, its branches, users, roles, and settings.
- Member: tenant user with access to their profile and member-facing dashboard.
- Branch: location/business-unit record under an organization.

## Backend

- Framework: Laravel 12 / PHP 8.2+
- Authentication: email/password login with JWT access and refresh tokens
- Authorization: Spatie permissions with organization-scoped roles
- Tenancy: users belong to organizations through `organization_users`; tenant context is derived from authenticated membership and organization switching
- Seed bootstrap: `DatabaseSeeder` calls `SaaSSeeder` for demo organization, branch, users, roles, and permissions

## Frontend

- Framework: Nuxt 3 / Vue 3
- Package manager: Bun preferred for frontend workflows
- Core surfaces: login, dashboard, organization switch, branch management, roles/permissions, users, profile

## Fresh setup checklist

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
```

## Local demo credentials

Seed-only local credentials:

| Persona | Email | Password |
| --- | --- | --- |
| Platform Administrator | `platform-admin@demo.com` | `password` |
| Organization Manager | `manager@demo.com` | `password` |
| Member | `member@demo.com` | `password` |

Do not use demo credentials in production.

## API smoke checklist

1. Run migrate/seed.
2. Start backend API.
3. `POST /api/login` with a seeded demo user.
4. Redact tokens before sharing evidence.
5. Use the access token to call:
   - `GET /api/me`
   - `GET /api/organizations/current`
   - `GET /api/navigation`
6. Confirm role, organization, branch, and neutral navigation match the seeded persona.

## Migration/removal note

The boilerplate conversion removes active domain-specific feature docs and navigation for Coach, Course, CourseCategory, Equipment, Level, Member Package, Remark, and related booking/commerce surfaces. Some legacy files can remain during incremental cleanup for compatibility, but new documentation and seeds should describe only neutral SaaS primitives unless a future product scope explicitly reintroduces a domain module.
