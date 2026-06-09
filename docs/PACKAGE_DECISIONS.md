# Package Decisions

Purpose: record which backend packages are intended core parts of this boilerplate, which packages are optional/deferred, and when each optional package should be used.

Last updated: 2026-06-10

---

## Summary

| Package / technology | Status | Recommendation |
|---|---|---|
| Spatie Activitylog | Intended core | Keep |
| Laravel Telescope | Intended dev/debug core | Keep, protect/disable in production |
| Spatie Media Library | Intended core | Keep |
| OpenSpout | Intended core | Keep |
| Midtrans | Intended optional SaaS billing/payment | Keep installed or optional, wire when payment flow is planned |
| DomPDF | Optional billing/report support | Use if invoices/receipts/report PDFs are needed |
| Laravel Scout | Optional search abstraction | Use only with real search requirements |
| Typesense | Optional search engine | Use with Scout if full-text/faceted search is needed |
| Firebase | Optional external auth/FCM integration | Avoid unless explicitly needed |
| ClickHouse client | Optional analytics backend | Avoid for core; add later for high-volume analytics |
| Spatie Multitenancy | Optional / needs verification | Use only if it adds value beyond current organization_id scoping |

---

## Intended core packages

These packages are intended parts of the boilerplate. Future agents should not remove them casually.

### Spatie Activitylog

Status: intended core.

Use for:
- audit trail
- who changed what
- role/permission changes
- organization setting changes
- billing/subscription changes
- sensitive staff/customer/member updates

Recommendation:
- Keep.
- Add consistent activity logging on critical business actions.
- Avoid logging secrets, raw tokens, or sensitive payment payloads.

### Laravel Telescope

Status: intended development/debugging core.

Use for:
- local request inspection
- query debugging
- exceptions
- jobs/events/cache/mail debugging
- development-time observability similar to a Laravel-native debug dashboard

Recommendation:
- Keep for development.
- Ensure production is protected or disabled.
- If production observability is needed, prefer a real observability stack such as OpenTelemetry + SigNoz/Grafana rather than exposing Telescope publicly.

Production rule:
- Never expose Telescope publicly without strict authorization.

### Spatie Media Library

Status: intended core.

Use for:
- avatars
- organization logos
- branch images
- staff/customer/member documents
- uploaded files
- future product media/assets

Recommendation:
- Keep.
- Define media collections explicitly per model.
- Add file type, size, and authorization rules.

### OpenSpout

Status: intended core.

Use for:
- customer/member import
- staff import
- transaction export
- report export
- Excel/CSV workflows

Recommendation:
- Keep.
- Use for large spreadsheet import/export instead of memory-heavy spreadsheet libraries.
- Validate imports with preview/dry-run where possible.

---

## Optional / deferred packages

These packages may be useful, but should not be wired into core flows until the product need is explicit.

### Midtrans

Status: intended optional SaaS billing/payment package.

Best when:
- the SaaS targets Indonesia payments
- subscription checkout is needed
- invoice/payment status flow is planned
- payment webhook handling will be implemented and tested

Recommendation:
- Keep as optional if SaaS billing is in scope.
- Do not hard-wire until subscription/payment lifecycle is designed.
- Implement with idempotent webhook handling and signature verification.

Required before production:
- payment creation flow
- webhook signature validation
- idempotency
- transaction state machine
- subscription activation on settlement
- retry/failure handling
- audit logs

### DomPDF

Status: optional billing/report support.

Best when:
- invoice PDFs are required
- receipt PDFs are required
- printable reports are required

Recommendation:
- Use with Midtrans/subscription billing if invoice/receipt PDFs are needed.
- Delay if PDF output is not needed yet.
- For large reports, prefer spreadsheet export or queued PDF generation.

### Laravel Scout + Typesense

Status: optional search stack.

Best when:
- customer/member search needs typo tolerance
- global search is needed
- faceted/filterable search is needed
- dataset is large enough that simple SQL LIKE/full-text is insufficient

Recommendation:
- If full-text search is needed, use Scout + Typesense together:
  - Scout = Laravel indexing abstraction
  - Typesense = fast search engine
- If search is simple, start with database search first.
- Do not add search infrastructure before real search requirements exist.

Use database search first for:
- small customer lists
- exact email/phone/member-code lookup
- admin tables with normal filters

Use Typesense when:
- fuzzy search matters
- autocomplete matters
- multi-field ranking matters
- search must stay fast at larger scale

### Firebase

Status: optional external ecosystem integration.

Best when:
- Firebase Auth is explicitly selected
- Firebase Cloud Messaging is needed for push notifications
- the product already depends on Firebase ecosystem

Recommendation:
- Avoid for now unless a specific Firebase feature is chosen.
- Do not mix Firebase Auth with JWT/Sanctum casually.
- If Firebase is used only for Google login, consider normal OAuth/Socialite-style login instead to reduce auth complexity.

Risk:
- Can confuse auth architecture if used alongside JWT/Sanctum without clear source of truth.

### ClickHouse client

Status: optional analytics backend.

Best when:
- high-volume event analytics is needed
- request/business events are too large for OLTP DB analytics
- reporting requires large aggregate scans
- telemetry/product analytics becomes a real product requirement

Recommendation:
- Avoid in core boilerplate flows.
- Add later when analytics volume justifies it.
- PostgreSQL/MySQL is enough for normal SaaS CRUD and reports at first.

Risk:
- Adds operational overhead.
- Adds a second data store before the product needs it.

### Spatie Multitenancy

Status: optional / needs verification.

Current convention:
- Tenant boundary is `organization_id`.
- Branch is operational context.
- Current app appears to use custom tenant middleware, app container context, and `organization_id` global scopes.

Recommendation:
- Verify actual usage before relying on this package.
- If the app stays single-database with `organization_id` scoping, Spatie Multitenancy may be unnecessary complexity.
- Use it only if it provides clear value for tenant context tasks, tenant-aware queues, tenant switching, or future multi-database tenancy.

Risk:
- Future agents may assume tenancy is package-driven while actual code is custom organization-context driven.

---

## Recommended default package profile

### Keep and actively use

- Spatie Activitylog
- Laravel Telescope
- Spatie Media Library
- OpenSpout
- Spatie Permission

### Keep optional / wire when planned

- Midtrans
- DomPDF
- Scout + Typesense

### Avoid until strong reason

- Firebase
- ClickHouse

### Verify before deeper use

- Spatie Multitenancy

---

## Decision rules for future agents

Do not remove a package only because it looks unused.

First classify it as:
- intended core
- intended dev-only
- optional enabled
- optional deferred
- remove candidate

Before wiring optional packages:
- write the product flow
- add config/env guards
- add tests
- ensure missing env values do not break fresh setup
- keep implementation behind service boundaries

Before removing optional packages:
- search code usage
- check docs
- check composer scripts/config
- verify no migrations/models depend on it
- ask user if uncertain

---

## Related docs

- `docs/BOILERPLATE_CONVENTIONS.md`
- `docs/BOILERPLATE_ISSUES.md`
