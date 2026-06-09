# API Route Conventions

Purpose: make route style, context, and compatibility rules clear before backend/frontend refactors.

Source docs: `docs/BOILERPLATE_CONVENTIONS.md`, `docs/PACKAGE_DECISIONS.md`.
Code inspected: `saas-backend/routes/api.php`.
Last updated: 2026-06-10

---

## Decision

Use explicit route groups by context:

```text
/api/platform/*        platform/global routes
/api/organizations/*   organization-owned resources and context switch
/api/branches/*        neutral branch resource alias using active organization context
/api/...               authenticated user/account or legacy-compatible aliases
```

Prefer neutral, Laravel-like API resource routes for primary clients, with nested organization routes only when they improve clarity or preserve compatibility.

---

## Context classes

| Class | Pattern | Context | Examples |
|---|---|---|---|
| Public | `/api/login`, `/api/register` | none | auth start, password reset, org slug lookup |
| Auth user | `/api/me`, `/api/profile` | authenticated user | profile, memberships |
| Platform/global | `/api/platform/...` or explicitly named global resources | platform user | platform admin/module catalog if global |
| Organization | `/api/organizations/current`, `/api/roles`, `/api/staffs` | active organization | settings, roles, staff, billing |
| Branch | `/api/branches`, operational resources | active organization + branch when operation requires it | branch CRUD, appointments, POS |

---

## Organization and branch routing style

### Preferred primary style

Use active context headers/claims for most app APIs:

```text
GET /api/branches
POST /api/branches
GET /api/staffs
GET /api/roles
Authorization: Bearer ...
X-Organization-ID: <org_id>
X-Branch-ID: <branch_id> when branch-scoped
```

Why:

- Keeps frontend routes stable while organization/branch switch changes context.
- Avoids duplicating organization IDs in every URL and header.
- Matches current `routes/api.php` neutral `branches` route comment.

### Compatibility / explicit nested style

Nested routes are acceptable when preserving current clients or making admin context explicit:

```text
GET /api/organizations/{organization}/branches
POST /api/organizations/{organization}/switch
```

Rules for nested routes:

- Validate `{organization}` belongs to the authenticated user.
- Ensure `{organization}` matches or intentionally overrides active organization context.
- Do not allow nested route params to bypass tenant resolver validation.

---

## Current observed route map

Public/auth start:

- `POST /api/login`
- `POST /api/auth/refresh`
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `POST /api/register`
- `POST /api/auth/google`
- `GET /api/organizations/by-slug/{slug}`

Authenticated group under `jwt`:

- `GET /api/navigation`
- `GET /api/me`, `GET/PUT /api/profile`, `POST /api/change-password`
- `Route::apiResource('/api/branches')`
- `GET /api/organizations`, `GET /api/organizations/current`, `GET /api/organizations/{organization}/show`
- `POST /api/organizations/{organization}/switch`, `POST /api/organizations/{organization}/set-default`
- `Route::apiResource('/api/organizations/branches')` nested under organizations prefix
- `Route::resource('/api/organizations/roles')` and `/api/organizations/permissions`
- `GET /api/current-tenant`
- `Route::resource('/api/staffs')`
- `GET /api/saas/plans`, `POST /api/saas/subscribe`

Observed discrepancies:

- Role routes live under `/api/organizations/roles`, which reads like roles are a subresource of the collection, not a selected organization. Prefer `/api/roles` with active org, or `/api/organizations/{organization}/roles` for nested explicit style.
- `POST /api/logout` is public and returns `true`; should be authenticated or removed if client-only token clearing is the chosen behavior.
- `options` routes are public; verify no tenant/private data leaks through options endpoints.

---

## Naming conventions

- Use plural resource names: `organizations`, `branches`, `staffs` or migrate to `staff` only with a compatibility alias plan.
- Use Laravel resource verbs and status codes.
- Use explicit action routes for state changes that are not CRUD: `switch`, `set-default`, `subscribe`, `refresh`.
- Name routes consistently; avoid duplicate unnamed legacy aliases once clients migrate.
- Prefer dotted permissions matching resource/action: `branch.view`, `role.assign`, `organization.manage_billing`.

---

## Request/response rules

- Never accept client `organization_id` in payload as authority without membership validation.
- Prefer context headers for active organization/branch over payload IDs.
- Branch payloads must be forced to active organization server-side.
- Pagination should be Laravel-like and stable for frontend tables.
- Error responses should include a clear `message`; machine `error` codes are recommended for context failures.

Suggested context errors:

```text
400 organization_context_required
403 organization_access_denied
404 branch_not_found
400 branch_context_required
403 permission_denied
```

---

## Optional package route guardrails

Do not add package-backed routes until product flow and tests exist:

- Midtrans: add payment/webhook routes only with signature verification, idempotency, state-machine tests.
- DomPDF: add invoice/report PDF routes only after document requirements exist.
- Scout/Typesense: add search endpoints only after basic DB search is insufficient.
- Firebase: avoid auth routes beyond explicitly chosen external login/account-linking flow.
- ClickHouse: no analytics API dependency for core CRUD.
- Spatie Multitenancy: no package-driven tenant routes unless architecture changes.

---

## Open questions / follow-ups

1. Decide whether `/api/organizations/roles` remains as compatibility alias or migrates to `/api/roles` / `/api/organizations/{organization}/roles`.
2. Audit public `/api/options/*` for tenant/private leakage.
3. Fix public logout route or document it as intentional client-only logout.
