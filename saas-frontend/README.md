# SaaS Boilerplate Frontend

Nuxt frontend for a reusable SaaS starter application.

## Runtime surfaces

- Public: landing page, pricing, help center, privacy policy, login, registration, password reset.
- Authenticated: dashboard, profile, account settings, users/team, roles/permissions, and generic management shell.
- Removed from active frontend: legacy domain feature pages and navigation for fitness, scheduling, commerce, and other business-specific modules.

## Commands

```bash
bun install
bun run typecheck
bun run build
bun run dev
```

## Notes

This app keeps generic Vuexy/Nuxt admin UI building blocks where they support the neutral SaaS shell. Product-specific routes, copy, stores, composables, and feature components should stay out of the active user-facing app unless a future task explicitly reintroduces them.
