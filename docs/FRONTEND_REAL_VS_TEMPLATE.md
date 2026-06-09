# Frontend real-vs-template audit

Issue: RAJAA-103
Date: 2026-06-10
Scope: `saas-frontend/` Nuxt/Vue source, auth API client, organization/branch context, RBAC/ability/navigation behavior.

## Summary

The frontend is a Nuxt 4/Vue/Vuetify app built from a commercial SaaS template. It has real RAJAA/SaaS implementation slices for auth, organization context, branch management, users/staff, roles/permissions, profile, and dashboard shell. It also still carries template/demo marketing, pricing, help-center, UI navigation, and many reusable dialogs that are not yet wired to backend product behavior.

No files were deleted. Candidate deletion/quarantine needs CTO/product approval because some template areas may be intentionally kept as public marketing scaffolding.

## Source map

| Area | Classification | Keep/delete recommendation | Evidence / notes |
|---|---|---|---|
| `pages/login.vue`, `pages/register.vue`, `pages/forgot-password.vue`, `pages/reset-password.vue` | Real product | Keep | Auth entry points. Backed by `composables/useAuthRemember.ts`, token backup plugin, secure storage, org setup. |
| `pages/manage/index.vue` | Real product | Keep | Authenticated manage/dashboard landing. |
| `pages/manage/branches/index.vue` | Real product | Keep | Uses `composables/useBranches.ts`; branch CRUD path matches Phase 1/3 conventions. |
| `pages/manage/users/[tab].vue`, `components/users/*` | Real product | Keep | Staff/user management UI. Needs backend contract stabilization before deeper cleanup. |
| `pages/manage/roles/*`, `components/users/Permission.vue`, `components/dialogs/AddEditRoleDialog.vue`, `AddEditPermissionDialog.vue` | Real product | Keep | Role/permission management UI. Uses `composables/useRoles.ts`. |
| `pages/manage/account-settings/[tab].vue`, `components/AccountSettings/*` | Mixed real/template | Keep for now | Account/security UI partly useful, but template-only security/payment assumptions need later review. |
| `pages/profile.vue`, `components/profile/*` | Mixed real/template | Keep for now | Profile shell is useful; QR/bio details may be template/demo until backend contract exists. |
| `components/OrganizationSwitcher.vue`, `stores/organizationStore.ts` | Real product | Keep | Central org/branch context owner. Needs cache/refresh hardening noted below. |
| `composables/useApi.ts`, `useTokenRefresh.ts`, `useSecureStorage.ts`, `useAuthRemember.ts` | Real product | Keep | Central API/auth layer. Small 401/session cleanup risk exists. |
| `composables/useNavigation.ts` | Real product candidate | Keep | Fetches backend `/navigation`; not clearly wired into layout yet. Useful direction because backend filters by permissions. |
| `composables/useFilteredNavigation.ts`, `navigation/vertical/master.ts`, `dashboard.ts` | Transitional | Keep temporarily | Local CASL-filtered nav exists, but static template nav files also exist. Must converge to backend `/navigation` or one local source. |
| `navigation/vertical/ui-elements.ts`, `forms.ts`, `charts.ts`, `others.ts`, horizontal equivalents | Template/demo | Quarantine candidate | Template UI demo navigation. Should not ship in product nav unless intentionally enabled. |
| `pages/front-pages/landing-page/index.vue` | Intentionally kept template candidate | Keep pending CEO/product | Public landing scaffold may be useful, but copy/assets are template. |
| `pages/front-pages/pricing.vue`, `components/AppPricing.vue`, pricing dialogs | Template/payment placeholder | Quarantine until payment phase | Payment lifecycle is Phase 5; current pricing UI may misrepresent subscription readiness. |
| `pages/front-pages/help-center/*` | Template/demo | Quarantine candidate | No product support content/backend contract found. |
| `pages/privacy-policy.vue` | Intentionally kept template candidate | Keep but rewrite content | Legal page scaffold useful; content must be RAJAA-specific before launch. |
| `pages/access-control.vue`, `pages/not-authorized.vue` | Mixed | Keep `not-authorized`; review `access-control` | `not-authorized` supports middleware; `access-control` may be template demo. |
| `layouts/*`, `@layouts/*`, `plugins/vuetify/*`, `plugins/iconify/*` | Framework/template foundation | Keep | Layout system and theme used by app; expensive to replace now. |
| `components/dialogs/AddPaymentMethodDialog.vue`, `PaymentProvidersDialog.vue`, `PricingPlanDialog.vue`, `UserUpgradePlanDialog.vue` | Template/payment placeholder | Quarantine until Phase 5 | Do not expose before subscription backend is hardened. |
| `components/dialogs/CreateAppDialog.vue`, `ShareProjectDialog.vue`, `ReferAndEarnDialog.vue`, address/card dialogs | Template/demo | Quarantine candidate | No matching product backend found. |
| `components/global/CommandPalette.vue` | Transitional | Keep but harden later | Contains hard-coded product quick links; should be permission/context filtered. |
| `components/dashboard/shared/QuickActionCard.vue`, `common/AppDatatable.vue`, `GlobalSnackbar.vue`, `AppLoadingIndicator.vue` | Real/shared | Keep | Shared primitives. |

## Auth and 401 behavior

| Behavior | Current finding | Risk | Recommendation |
|---|---|---|---|
| Access token header | `useApi.ts` reads `auth.token` cookie and sends `Authorization: Bearer ...`. | OK if cookie is always synced with secure storage. | Keep central. |
| Org/branch headers | `useApi.ts` sends `X-Organization-ID` and `X-Branch-ID` from `organizationStore`, falling back to cookies. | OK for API calls, but cookie/state can drift if branch set directly. | Persist branch through `setCurrentBranch()` only. |
| 401 handling | `useApi.ts` attempts refresh on `token_expired`, then returns a retry-required error. If refresh fails, tries `useAuth().signOut()`, then secure-storage clear and `navigateTo('/login')`. | Nuxt composables inside `createFetch` interceptor can lose context; fallback still uses `navigateTo`, which can fail outside Vue/Nuxt context. Org cookies/store are not cleared in primary signOut path. | Move session-expired cleanup to a browser-safe helper or ensure only Nuxt plugin context uses composables. Clear org/branch cookies on forced logout. |
| Refresh failure | `useTokenRefresh.ts` clears secure tokens and calls `navigateTo('/login')`. | Same composable context risk; org/branch cookies not cleared. | Use client `window.location.assign('/login?reason=session_expired')` fallback when no Nuxt context. |
| Retry after refresh | `useApi.ts` does not auto-retry; returns `{ tokenRefreshed: true }` error. | Call sites may show errors until manual retry. | Later task: wrap retry centrally or use `$fetch` interceptor pattern. |

## Organization / branch context behavior

Expected sequence:

1. Login succeeds.
2. `useAuthRemember.ts` stores access/refresh tokens and user data.
3. Login response organizations are stored in `organizationStore`.
4. Current organization is selected from backend `current_organization` or default org.
5. `setCurrentOrganization()` auto-selects first active branch and writes `current-organization-id` cookie.
6. Branch switching must call `setCurrentBranch()` so `current-branch-id` cookie stays in sync.
7. Every API call through `useApi()` receives current org/branch headers.
8. Navigation/ability state should be refreshed after org/branch switch before rendering protected links.

Findings:

| Item | Current finding | Risk | Action |
|---|---|---|---|
| Org switch | `organizationStore.switchOrganization()` calls `/organizations/{id}/switch`, updates tokens, calls `setCurrentOrganization()`. | Good base. | Keep. |
| Branch from switch response | If `response.data.branch` exists, code assigns `this.currentBranch = response.data.branch` directly. | Branch cookie may still point to auto-selected branch, not backend-selected branch. | Small fix recommended: use `this.setCurrentBranch(response.data.branch)`. |
| Branch-only switch | No explicit backend branch switch endpoint found in frontend. Branch selection likely local via `setCurrentBranch()`. | If backend expects branch-bound token/abilities, local branch switch is insufficient. | Confirm backend contract from RAJAA-101; add `/branches/{id}/switch` if backend supports token-scoped branch. |
| Ability refresh | `usePermission.ts` watches `orgStore.currentRole` and updates CASL ability. | Role-only. If branch changes permissions/navigation, ability will not refresh. | If permissions are branch scoped, watch `currentBranchId` too or source abilities from backend. |
| Navigation cache | `useNavigation.ts` local refs are per-composable call and no user-only global cache found. | Low cache staleness risk, but it also means layouts may not share backend nav unless wired. | On org/branch switch, call `fetchNavigation()` in layout/store if backend nav is the source of truth. |
| Static nav | `useFilteredNavigation.ts` filters `navigation/vertical` static items using CASL. | Static template nav can diverge from backend-permission nav. | Choose one source; recommended backend `/navigation` for SaaS org/branch-aware nav. |

## Real product paths to verify manually

| Flow | Expected check |
|---|---|
| Login with valid user | Tokens stored; organization list set; current organization and first active branch selected; manage dashboard opens. |
| Expired access token + valid refresh token | First failing API refreshes token; subsequent retry succeeds or UI retries centrally. |
| Expired/invalid refresh token | Tokens and org/branch cookies clear; user lands on `/login?reason=session_expired` or `/login`; protected pages inaccessible. |
| Organization switch | New token stored; `current-organization-id` changes; branch resets to selected org branch; abilities/navigation refresh before protected nav render. |
| Branch switch | `current-branch-id` changes; API calls include new branch header; branch-scoped tables/nav refresh. |
| Forbidden page | ACL sends user to `not-authorized`; no template access-control demo leaks into app nav. |

## Small code fixes applied in this task

See changed files:

- `saas-frontend/stores/organizationStore.ts`: persist backend-selected branch through `setCurrentBranch()` during org switch.

## Follow-up tasks recommended

1. Frontend Developer: replace template/demo nav imports with a single nav source. Preferred: backend `/navigation` via `useNavigation()` in authenticated layout.
2. Frontend Developer: add a browser-safe `expireSession()` helper used by `useApi.ts` and `useTokenRefresh.ts` to clear secure tokens, auth cookie, user data, org/branch cookies, and redirect without relying on Nuxt context.
3. Frontend Developer + Backend Developer: confirm branch switch contract. If branch affects permissions, implement token/context refresh endpoint and refetch abilities/navigation after switch.
4. QA: after backend routes are runnable, execute login, 401 refresh failure, org switch, branch switch, and forbidden route browser checks with screenshots/network evidence.
