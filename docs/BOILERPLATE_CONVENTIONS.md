# SaaS Boilerplate Conventions

Purpose: define intended architecture and naming conventions for this SaaS boilerplate so future developers and AI agents do not confuse intended behavior with bugs.

This document is the source of truth for current intended conventions unless later architecture docs supersede it.

Last updated: 2026-06-10

---

## 1. Product model

This boilerplate is intended for multi-company SaaS products where one platform hosts many business tenants.

Examples of target businesses:
- studios
- clinics
- gyms
- schools/courses
- salons
- service businesses with multiple outlets

High-level ownership:

```text
SaaS Platform Owner
└── Organizations / Tenants
    └── Branches / Business Locations
```

The SaaS platform owner owns and operates the software. Each organization is a business tenant using the software. Each organization can have multiple branches.

---

## 2. Core terms

| Term | Meaning | Scope |
|---|---|---|
| Platform / SaaS owner | The owner/operator of this SaaS application | Global |
| Organization | A tenant/business using the SaaS | Tenant boundary |
| Branch | A business location/outlet under an organization | Organization child |
| User | Global login identity/account | Global |
| Staff | A user working for an organization | Organization membership |
| Customer / Member | A customer profile inside an organization | Organization-scoped |
| Role definition | A named role such as Owner, Manager, Staff | Organization-scoped |
| Role assignment | Assignment of a role to a user for a branch | Branch-scoped |
| Permission | Capability string such as `staff.create` | Global catalog, attached to roles |

---

## 3. Tenant boundary convention

The tenant boundary is `organization_id`.

Organization is the primary isolation boundary. Data owned by one organization must not be visible to another organization unless explicitly designed as platform/global data.

Examples of organization-scoped data:
- branches
- staff memberships
- customer/member profiles
- transactions
- subscriptions
- organization settings/configurations
- organization-owned role definitions

Rules:
- Tenant-bound models should have `organization_id`.
- Tenant-bound API routes must resolve active organization.
- Never trust client-provided `organization_id` without validating user access.
- If a user belongs to multiple organizations, requests must resolve which organization is active.

---

## 4. Branch convention

A branch represents a physical or operational business location under an organization.

Branch belongs to organization:

```text
branches.organization_id -> organizations.id
```

Branches are not separate tenants. Branches are locations inside one tenant.

Rules:
- Branch must always belong to the active organization.
- Branch-scoped operations require active branch context.
- Branches share organization-level customer/member data.
- Operational records may be branch-specific when the business action happened at one location.

Examples of branch-scoped records:
- appointments/bookings
- transactions/POS sales
- attendance/visits
- room/resource schedules
- inventory movement, if stock is per branch
- staff duty/shift assignments

Examples of organization-scoped records shared across branches:
- customer/member profile
- membership package entitlement, unless intentionally branch-limited
- customer notes, unless intentionally branch-private
- role definitions
- billing/subscription

---

## 5. User, staff, and customer/member convention

### 5.1 User is global identity

`users` represents a global login identity.

A single user/account/email can participate in multiple organizations.

Example:

```text
User U1: budi@example.com
- Owner of Organization A
- Manager in Branch 1 of Organization A
- Customer/member in Organization A
- Customer/member in Organization B
- Staff in Organization C
```

This is intended behavior.

### 5.2 Staff is organization membership

A staff user is a global user attached to an organization.

Recommended concept:

```text
organization_users
- organization_id
- user_id
- role or membership metadata
- default/current flags if needed
```

The organization/client/owner can have multiple staff users.

A staff user can have different roles in different branches.

Example:

```text
Organization: Studio ABC
Branches:
- Branch A
- Branch B

User: Budi
Role assignments:
- Branch A: Staff
- Branch B: Manager
```

This is intended and preferred for multi-branch SaaS.

### 5.3 Customer/member is organization-scoped

Customer/member should be modeled as an organization-scoped profile, not only as a `users` row.

Reason:
- Same global account can be a customer in multiple organizations.
- Each organization may have different member code, package, status, notes, preferences, and history.
- Owner/staff users may also be customers/members.

Recommended concept:

```text
members/customers
- id
- organization_id
- user_id nullable or required
- member_code
- status
- profile/customer-specific fields
```

Recommended uniqueness:
- If one customer profile per user per organization: unique `(organization_id, user_id)`.
- If offline/walk-in customers are allowed: `user_id` may be nullable and matching rules must be explicit.

Do not make customer email globally unique at the customer/member level if the same person can register under multiple organizations.

---

## 6. RBAC convention

This project intentionally uses a hybrid RBAC model:

```text
Role definitions belong to organizations.
Role assignments are scoped to branches.
Spatie team key means branch_id.
```

### 6.1 Role definitions are organization-scoped

Roles are defined per organization.

Example:

```text
roles
- id
- organization_id
- name
- guard_name
```

Reason:
- Organization A can have its own Manager role.
- Organization B can have a different Manager role with different permissions.
- Platform can later support custom roles per tenant.

### 6.2 Role assignments are branch-scoped

A user can have different roles in different branches.

Example:

```text
model_has_roles
- role_id: Manager role for Organization A
- model_type: App\Models\User
- model_id: user id
- branch_id: Branch B
```

This means:
- The user is Manager only in Branch B.
- The same user may be Staff in Branch A.

### 6.3 Spatie teams convention

Spatie Permission `teams` feature is used for branch context.

Current intended config:

```php
'permission.teams' => true
'permission.column_names.team_foreign_key' => 'branch_id'
```

Important:
- Spatie "team" means branch.
- Spatie "team" does not mean organization.
- Organization is still the tenant boundary.

### 6.4 Active branch must be set for permission checks

When checking branch-scoped permissions, the app must set the active Spatie team id to the active branch id.

Conceptual example:

```php
setPermissionsTeamId($branchId);
```

Use the correct API for the installed Spatie Permission version.

Setting `app()->instance('branch_id', $branchId)` alone may not be enough for Spatie permission checks. Middleware must verify and set Spatie's active team/branch context.

### 6.5 No-branch behavior

Routes must define whether branch context is required.

Recommended convention:

| Route/action type | Required context |
|---|---|
| Platform admin | platform user, no organization |
| Organization settings | organization |
| Organization billing/subscription | organization |
| Role definition management | organization |
| Customer/member list/profile | organization |
| Staff list | organization |
| Staff operational action | organization + branch |
| Appointments/bookings | organization + branch |
| Branch reports | organization + branch |
| Organization-wide reports | organization, optional branch filter |

If a branch-scoped operation has no active branch, prefer deny with a clear error instead of silently choosing an unsafe context.

Default branch can be used for UX only if documented and tested.

---

## 7. Permission naming convention

Permission names should be dotted and domain-based.

Examples:

```text
organization.view
organization.update
organization.manage_billing
branch.view
branch.create
branch.update
branch.delete
staff.view
staff.create
staff.update
staff.delete
staff.reset_password
role.view
role.create
role.update
role.delete
role.assign
member.view.any
member.view.own
member.create
member.update.basic
member.delete
member.import
member.export
transaction.create
transaction.view
transaction.refund
transaction.void
report.view_financial
report.view_occupancy
report.export
dashboard.view_stats
```

Rules:
- Seeder and RBAC initialization must use the same permission catalog.
- Do not mix old flat names like `manage_users` with dotted names unless there is a compatibility alias plan.
- Prefer central permission declarations to avoid drift.

---

## 8. Customer/member visibility convention

Branches within one organization share customer/member data.

Example:

```text
Organization: Gym ABC
Branches:
- Kemang
- BSD

Customer: Siti
Registered under Gym ABC
Visible to both Kemang and BSD branches
```

Branch-specific records can still exist:

```text
Siti's member profile: organization-scoped
Siti's visit at Kemang: branch-scoped
Siti's transaction at BSD: branch-scoped
```

This avoids duplicate customer identities across branches while preserving branch-level operations and reports.

---

## 9. Multi-organization user convention

A global user can be attached to multiple organizations in different capacities.

Example:

```text
User: owner@example.com
- Owner of Organization A
- Customer/member of Organization A
- Customer/member of Organization B
- Staff of Organization C
```

Rules:
- Active organization must be explicit in authenticated API context.
- Switching organization must update active organization, available branches, active branch/default branch, role context, abilities, and navigation.
- Permission checks must not aggregate roles across organizations.
- Customer/member profile is resolved by `(organization_id, user_id)` or equivalent membership identifier.

---

## 10. API context convention

Tenant-aware API requests should have clear context.

Recommended headers/claims:

```text
Authorization: Bearer <token>
X-Organization-ID: <organization_id>    optional override / active org
X-Branch-ID: <branch_id>                required for branch-scoped operations
```

JWT/session claims may include:
- user id
- active organization id
- active role or role summary
- default branch id

Rules:
- Header override must be validated against user membership.
- Branch must be validated against active organization.
- Do not let client select arbitrary organization or branch without membership checks.
- Tenant middleware should set request attributes and app context consistently.
- Spatie team/branch context must be set before permission middleware/checks run.

---

## 11. Frontend convention

Frontend should treat organization and branch as active context.

Expected behavior:
- User can switch organization if they belong to multiple organizations.
- After organization switch, frontend refreshes:
  - active organization
  - branch list
  - default/active branch
  - ability rules/permissions
  - navigation/menu
  - profile context
- User can switch branch if they have access to multiple branches.
- Branch switch refreshes permissions/navigation if roles differ by branch.

Important:
- A user being Manager in Branch B but Staff in Branch A means frontend must not cache permissions globally across branches.
- Permission/ability cache should be scoped by organization + branch.

---

## 12. Data modeling examples

### 12.1 Staff different role per branch

```text
users
- U1 Budi

organizations
- O1 Studio ABC

branches
- B1 Studio ABC Kemang, organization_id O1
- B2 Studio ABC BSD, organization_id O1

roles
- R1 Staff, organization_id O1
- R2 Manager, organization_id O1

organization_users
- O1, U1

model_has_roles
- R1, U1, branch_id B1
- R2, U1, branch_id B2
```

Interpretation:
- Budi is Staff in Kemang.
- Budi is Manager in BSD.

### 12.2 Same user as customer in multiple organizations

```text
users
- U2 Siti, siti@example.com

organizations
- O1 Studio ABC
- O2 Clinic XYZ

members/customers
- C1, organization_id O1, user_id U2, member_code ABC-001
- C2, organization_id O2, user_id U2, member_code XYZ-777
```

Interpretation:
- Same login identity.
- Separate customer/member profiles per organization.
- Organization A cannot see Organization B's customer profile or history.

### 12.3 Owner also customer in own organization

```text
users
- U3 Owner Person

organizations
- O3 Gym Owner's Business

organization_users
- organization_id O3, user_id U3, role owner

members/customers
- organization_id O3, user_id U3, member_code GYM-OWNER-001
```

Interpretation:
- U3 manages the business.
- U3 can also consume services as a customer/member.

---

## 13. Testing requirements for these conventions

Future agents should add tests proving these conventions.

Required tests:
- User can belong to multiple organizations.
- User can switch active organization only to organizations they belong to.
- Branch must belong to active organization.
- Same staff user can have Staff role in Branch A and Manager role in Branch B.
- Permission checks differ by active branch.
- Customer/member data is visible across branches in one organization.
- Customer/member data is isolated between organizations.
- Same global user can be customer/member in multiple organizations.
- Organization owner can also be customer/member in their own organization.
- Permission catalog in seeder matches RBAC initialization.

---

## 14. Common mistakes to avoid

Do not:
- Treat Spatie team as organization in this project.
- Scope customer/member profiles by branch unless explicitly intended.
- Cache permissions only by user id; include organization and branch context.
- Allow branch id from request without validating organization ownership.
- Allow organization id from request without validating user membership.
- Insert directly into Spatie pivot tables in many places.
- Mix flat permissions and dotted permissions without alias strategy.
- Let tenant-scoped controllers run without tenant middleware/context.

Prefer:
- Centralized tenant resolver.
- Centralized role assignment service.
- Centralized permission catalog.
- Explicit route groups for platform, organization, and branch contexts.
- Tests before refactoring RBAC or tenancy.

---

## 15. Relation to issue tracker doc

See also:
- `docs/BOILERPLATE_ISSUES.md`

That document lists current known/suspected problems. This document defines intended conventions. If an item in the issue doc conflicts with this convention doc, update the issue item as "intended but needs implementation/testing/documentation" instead of treating it as a bug.
