# RBAC Architecture

Purpose: define role, permission, and branch-team rules before refactors.

Source docs: `docs/BOILERPLATE_CONVENTIONS.md`, `docs/PACKAGE_DECISIONS.md`.
Code inspected: `config/permission.php`, `PermissionSeeder`, `routes/api.php`, tenancy middleware.
Last updated: 2026-06-10

---

## Decision

Use Spatie Permission with teams enabled. In this project, Spatie `team` means `branch_id`, not `organization_id`.

RBAC model:

```text
Role definitions belong to organizations.
Role assignments are scoped to branches.
Permission strings are a global catalog.
```

`organization_id` remains the tenant boundary. `branch_id` is the Spatie team key used to decide which branch role assignment applies.

---

## Spatie team configuration

Observed intended config in `saas-backend/config/permission.php`:

```php
'permission.teams' => true,
'permission.column_names.team_foreign_key' => 'branch_id',
```

Rules:

- Never treat Spatie team as organization in this codebase.
- Permission checks for branch-scoped operations must set Spatie active team to active `branch_id` before checking roles/permissions.
- Setting only `app()->instance('branch_id', $branchId)` is not enough unless middleware also sets Spatie's active team using the installed package API.
- Permission cache must not be treated as user-global; effective abilities depend on organization + branch.

---

## Role definitions

Role definitions are organization-scoped:

```text
roles
- id
- organization_id
- name
- guard_name
```

Intent:

- Organization A can define Manager differently from Organization B.
- Role names alone are not globally authoritative.
- Future custom roles remain tenant-safe.

Guardrails:

- Always validate role belongs to active organization before assignment/update/delete.
- Do not assign a role from one organization to a branch/user in another organization.
- Centralize role creation and assignment in a service instead of writing directly to Spatie pivots from many controllers.

---

## Role assignments

Role assignment is branch-scoped:

```text
model_has_roles
- role_id
- model_type: App\Models\User
- model_id: user id
- branch_id: active branch id / Spatie team id
```

Interpretation:

- Same user can be Staff in Branch A and Manager in Branch B.
- Permission checks must differ by active branch.
- Organization-level screens with no branch should not silently aggregate all branch roles unless the route explicitly defines that behavior.

---

## No-branch behavior

| Action type | Required context | RBAC behavior |
|---|---|---|
| Platform admin | platform user | no branch team |
| Organization settings | organization | org-level membership/owner policy, no branch unless designed |
| Organization billing | organization | owner/billing permission, no branch |
| Role definition management | organization | org-level role admin policy |
| Staff list | organization | organization membership; optional branch filter |
| Customer/member list/profile | organization | organization permissions, no required branch by default |
| Staff operational action | organization + branch | set Spatie team = branch_id |
| Appointments/POS/branch reports | organization + branch | set Spatie team = branch_id |
| Organization-wide reports | organization, optional branch filter | validate branch filter if present |

If a branch-scoped route lacks active branch, deny with a clear client-actionable error. Do not silently pick first branch for authorization.

---

## Permission catalog

Permission names should be dotted and domain based, for example:

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
role.view
role.create
role.update
role.delete
role.assign
member.view.any
member.view.own
transaction.create
report.view_financial
```

Rules:

- Seeder and organization onboarding must use the same central permission catalog.
- Avoid flat legacy names such as `manage_users` unless a compatibility alias plan exists.
- Do not spread permission string literals across controllers; prefer a central declaration/service.

Observed status:

- `PermissionSeeder` contains dotted permissions and says it must match `OrganizationService::initializeRbac()`.
- RAJAA-100/RAJAA-102 should verify and centralize this behavior.

---

## Authorization sequence

For branch-scoped routes:

1. Authenticate user.
2. Resolve active organization and validate membership.
3. Resolve active branch and validate it belongs to organization.
4. Set Spatie permission team id to active `branch_id`.
5. Run permission/policy check.
6. Run controller action under tenant scope.

For organization-scoped routes:

1. Authenticate user.
2. Resolve active organization and validate membership.
3. Run organization-level policy/permission check.
4. Run controller action under tenant scope.

---

## Open questions / follow-ups

1. Confirm current middleware sets Spatie active team id; if not, backend must add it before relying on branch permissions.
2. Centralize permission strings so `PermissionSeeder` and onboarding cannot drift.
3. Add tests where one user has different roles in two branches and the same permission passes/fails based on `X-Branch-ID`.
