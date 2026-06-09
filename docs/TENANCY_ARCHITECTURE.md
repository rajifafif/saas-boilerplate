# Tenancy Architecture

Purpose: define tenant context and isolation rules before refactors.

Source docs: `docs/BOILERPLATE_CONVENTIONS.md`, `docs/PACKAGE_DECISIONS.md`.
Code inspected: `saas-backend/routes/api.php`, `TenantMiddleware`, `TenantAwareMiddleware`.
Last updated: 2026-06-10

---

## Decision

`organization_id` is the tenant boundary. Branch is an operational context inside one organization; it is not a tenant.

Use the current single-database, organization-column tenancy model first. Spatie Multitenancy remains optional/deferred and must not become a second current-tenant source unless a deliberate migration is planned and tested.

---

## Context hierarchy

```text
Platform / SaaS owner
└── Organization / tenant       isolation boundary: organization_id
    └── Branch / location       operational context: branch_id
```

Core rules:

- Tenant-owned models should have `organization_id`.
- Branch-owned operational records should have both `organization_id` and `branch_id` or a branch relation that is validated against the active organization.
- Organization-scoped data is visible across branches in the same organization unless explicitly designed otherwise.
- Data from Organization A must never be visible to Organization B through branch filters, route params, search, reports, exports, or nested resources.

---

## Active organization source order

Intended request resolution order:

1. Auth token claim `org_id` set by the JWT middleware.
2. `X-Organization-ID` explicit override, only after membership validation.
3. Tenant subdomain, only when supported by product routing and mapped to a real organization.
4. No organization context.

No organization context is valid only for platform/global routes and user account routes that list available organizations. Organization-scoped routes should fail clearly when context is missing.

Observed discrepancy:

- `TenantMiddleware` follows this intent: token claim, validated header override, subdomain fallback.
- `TenantAwareMiddleware` currently reads `X-ORGANIZATION-ID` / `X-TENANT-ID` and finds an organization, but does not validate authenticated user membership. Treat it as legacy/incomplete until replaced or hardened.

---

## Active branch source order

Intended branch resolution:

1. `X-Branch-ID` header for branch-scoped API calls.
2. Default branch only for UX convenience after organization switch/login, not as silent authorization fallback.
3. No branch context.

Rules:

- Branch must belong to the active organization.
- Branch-scoped operations require an active branch and should deny with a clear error if missing.
- Organization-scoped operations may have no branch, or may accept branch as an optional filter after validation.
- Switching branch must refresh permissions/navigation if roles can differ by branch.

---

## Route context classes

| Route class | Required context | Examples |
|---|---|---|
| Public | none | login, register, password reset, org slug lookup |
| Platform/global | platform user, no org | platform admin, global module catalog if truly global |
| User account | authenticated user, optional org | profile, list memberships |
| Organization | authenticated user + active organization | org settings, staff list, member list, roles, billing |
| Branch | authenticated user + active organization + active branch | appointments, branch operations, branch reports, POS/inventory movement |

---

## Middleware responsibilities

Tenant resolver middleware should set context consistently in both places while legacy code exists:

```text
request attributes:
- organization_id
- organization_role
- branch_id

app container:
- organization_id
- organization_role
- branch_id
- currentOrganization when available
```

Required validation:

- If `X-Organization-ID` is present, validate the user belongs to that organization.
- If `X-Branch-ID` is present, validate the branch belongs to active organization.
- Apply tenant/global scopes only after context is established.
- Do not trust route `{organization}` params without checking membership and consistency with active context.

---

## Data ownership examples

Organization-scoped:

- customers/members
- staff membership
- role definitions
- billing/subscription
- organization settings

Branch-scoped:

- appointments/bookings
- branch schedules
- POS transactions or visits when tied to a location
- inventory movement if stock is per branch
- branch reports

Platform/global:

- SaaS owner/admin records
- package/module catalog if not tenant-customized
- global permission catalog strings

---

## Optional package guardrail

Spatie Multitenancy is not the primary tenancy system right now. Do not wire it into request lifecycle until:

- custom `organization_id` tenancy test gaps are known,
- a migration decision is documented,
- current-tenant source of truth is singular,
- queues/jobs/tasks are covered if package-managed tenant context is needed.

---

## Open questions / follow-ups

1. Replace or harden `TenantAwareMiddleware`; current membership validation is weaker than `TenantMiddleware`.
2. Add tests proving header override denial for organizations the user does not belong to.
3. Add tests proving branch IDs from another organization are rejected before controller logic.
