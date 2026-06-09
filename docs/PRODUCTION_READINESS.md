# Production Readiness Checklist

Purpose: short guardrail checklist for moving this boilerplate toward production safely.

Source docs:

- `docs/AUTH_ARCHITECTURE.md`
- `docs/TENANCY_ARCHITECTURE.md`
- `docs/RBAC_ARCHITECTURE.md`
- `docs/API_ROUTE_CONVENTIONS.md`
- `docs/PACKAGE_DECISIONS.md`

Last updated: 2026-06-10

---

## Auth

- [ ] All protected routes are behind the canonical auth middleware.
- [ ] Logout behavior is explicit: authenticated server-side revocation or documented client-only token clearing.
- [ ] Refresh validates signature, expiry, token type, user existence, and organization membership.
- [ ] Tokens/secrets are never logged.
- [ ] Firebase/external auth is disabled or fully documented/tested as a product flow.

## Tenancy

- [ ] Every tenant-owned model is scoped by `organization_id`.
- [ ] Active organization resolution order is implemented and tested.
- [ ] `X-Organization-ID` override validates authenticated user membership.
- [ ] Branch IDs are validated against active organization.
- [ ] Legacy tenancy middleware cannot bypass membership checks.

## RBAC

- [ ] Spatie teams are enabled with `branch_id` as team key.
- [ ] Middleware sets Spatie active team before branch-scoped permission checks.
- [ ] Role definitions cannot cross organizations.
- [ ] Role assignments cannot cross organization/branch boundaries.
- [ ] Permission catalog is centralized and seeder/onboarding cannot drift.

## API routes

- [ ] Public routes expose no tenant/private data.
- [ ] Platform, organization, branch, and user-account route contexts are distinguishable.
- [ ] Legacy aliases are documented and have a migration/removal plan.
- [ ] Error responses for missing/invalid context are clear and machine-readable.

## Optional packages

- [ ] Telescope is protected or disabled in production.
- [ ] Activitylog excludes secrets and sensitive raw payloads.
- [ ] Media uploads have type, size, storage, and authorization rules.
- [ ] Midtrans is wired only with signature validation, idempotency, and subscription state tests.
- [ ] Scout/Typesense, Firebase, ClickHouse, DomPDF, and Spatie Multitenancy are not wired without product flow and tests.

## Minimum test signal before release

- [ ] User can belong to multiple organizations.
- [ ] User cannot switch to an organization they do not belong to.
- [ ] Branch from another organization is rejected.
- [ ] Same user can have different roles in two branches and permission results differ by active branch.
- [ ] Customer/member data is shared across branches in one org and isolated across organizations.
- [ ] Seeder permission catalog matches onboarding/RBAC initialization.
