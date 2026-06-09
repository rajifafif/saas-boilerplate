# Auth Architecture

Purpose: make the intended authentication model explicit before refactors.

Source docs: `docs/BOILERPLATE_CONVENTIONS.md`, `docs/PACKAGE_DECISIONS.md`.
Code inspected: `saas-backend/routes/api.php`, `app/Http/Controllers/Api/AuthController.php`, tenancy middleware.
Last updated: 2026-06-10

---

## Decision

Use Laravel users as the global login identity and issue stateless Bearer tokens for API requests. Organization context is carried in signed token claims and may be explicitly overridden per request only after membership validation.

This project should not mix Firebase Auth, Sanctum SPA cookies, or third-party auth as competing sources of truth unless a product flow explicitly selects them and tests prove the integration.

---

## Token and request model

Canonical authenticated API request:

```text
Authorization: Bearer <access_token>
X-Organization-ID: <organization_id>   optional explicit active org override
X-Branch-ID: <branch_id>               required for branch-scoped operations
```

Observed token response shape from current code:

```text
access_token     JWT-like signed token, type=access, 7 day TTL
refresh_token    signed refresh token, type=refresh, 30 day TTL
token_type       Bearer
expires_in       access token TTL in seconds
token            legacy alias for access_token
organizations    memberships returned at login
```

Access token claims observed/intended:

```text
uid      user id
type     access
org_id   active organization id, nullable
role     organization membership role summary, nullable
```

Refresh token claims observed/intended:

```text
uid      user id
type     refresh
org_id   active organization id, nullable
```

Authority rules:

- `users` is the identity source of truth.
- Organization membership comes from the user/organization membership relation, not from client input alone.
- Token `org_id` is a default context claim, not permission proof by itself.
- `X-Organization-ID` may override token context only after validating the authenticated user belongs to that organization.
- `X-Branch-ID` may set branch context only after validating the branch belongs to the active organization.

---

## Login, refresh, switch, logout

| Flow | Intended behavior | Notes / current discrepancy |
|---|---|---|
| Login | Validate email/password or explicit external auth flow, return access + refresh tokens and available organizations. | Current code also has a Firebase token branch; keep optional/deferred unless product chooses Firebase. |
| Refresh | Validate refresh signature, expiry, `type=refresh`, user existence, and organization membership before issuing new tokens. | Current refresh revalidates membership for token `org_id`; good pattern. |
| Organization switch | Validate membership in target org, issue new access + refresh tokens with target org context, return active/default branch summary. | Route currently points to `OrganizationController::switch`; `AuthController::switchOrganization` also exists. Follow-up should unify ownership. |
| Logout | Invalidate/revoke server-side token material if available and clear client-held tokens. | Current `routes/api.php` has `POST /logout` returning `true` outside auth middleware; this contradicts `AuthController::logout` and needs a backend follow-up. |

---

## Cookie/header stance

Backend API convention is Bearer-token first. If the frontend stores tokens in cookies, that is a client storage detail; the API contract remains `Authorization: Bearer` unless a future auth decision explicitly moves to Sanctum SPA cookie sessions.

Guardrails:

- Do not add parallel auth state such as `accessToken` cookies, `refreshToken` cookies, and localStorage without documenting the single source of truth.
- Do not rely on unsigned `userData` cookies for authorization.
- Do not use Firebase Auth and local JWT/Sanctum auth simultaneously without one canonical identity-linking flow.
- Never log raw access tokens, refresh tokens, Firebase tokens, or payment/customer secrets.

---

## Route protection

Observed public routes:

- `POST /api/login`
- `POST /api/auth/refresh`
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `POST /api/register`
- `POST /api/auth/google`
- `GET /api/organizations/by-slug/{slug}`
- `GET /api/options/*` routes

Observed authenticated group:

- `Route::middleware(['jwt'])` wraps profile, navigation, modules, organizations, branches, staff, and subscription routes.

Required convention:

- Every user-specific, organization-scoped, branch-scoped, billing, staff, role, or permission route must be behind auth.
- Tenant-aware routes must run tenant context resolution before controller authorization logic.
- Branch-scoped permission checks must run after active branch has been validated and Spatie team context set.

---

## Open questions / follow-ups

1. `POST /api/logout` is currently public and returns `true`; route it through auth and controller logout in a backend task.
2. There are both auth-controller and organization-controller switch concepts; choose one canonical switch endpoint and document response schema.
3. Decide whether Firebase login remains installed as optional only, or whether a real Firebase product flow will own account linking and tests.
