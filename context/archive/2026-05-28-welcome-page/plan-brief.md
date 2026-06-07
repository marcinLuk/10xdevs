# Welcome Page — Plan Brief

> Full plan: `context/changes/welcome-page/plan.md`

## What & Why

Replace the default Laravel 13 welcome page with a clean, branded GardenLog landing page. The current page is a 224-line scaffold full of Laravel marketing copy and an animated logo — it tells users nothing about GardenLog. The new page should immediately communicate the app's purpose and guide users to register or log in.

## Starting Point

The root URL (`/`) renders `resources/views/welcome.blade.php`, which is the unmodified Laravel scaffold. It uses inline Tailwind CSS v4 (not Vite-bundled), dark mode, and two-column layout with Laravel branding. Auth links sit in a top-right header via Breeze conditionals.

## Desired End State

Visiting `/` shows a minimal white page with a single centered card containing the GardenLog logo, a "Welcome to GardenLog" headline, descriptive subtext, and prominent Register/Log in buttons (or a Dashboard link for authenticated users). No Laravel branding, no marketing copy, no dark mode.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) |
| --- | --- | --- |
| Brand identity | Use existing `<x-application-logo>` as-is | S-05 (branding-nav) owns logo updates; avoids duplicate work. |
| Welcome copy | "Welcome to GardenLog" + value prop subtext | Explains the app in one line; matches PRD positioning. |
| Dark mode | Light only | Roadmap explicitly specifies "light-themed, white background." |
| Auth redirect | No redirect — keep conditional buttons | User preference; authenticated visitors see Dashboard link. |
| Asset loading | Vite (`@vite`) instead of inline CSS | Matches guest layout pattern; enables caching and consistency. |

## Scope

**In scope:** Replace `welcome.blade.php` with centered-card layout, logo, headline, auth buttons, Vite assets

**Out of scope:** Logo/brand design (S-05), dark mode, route changes, backend changes, new components

## Architecture / Approach

Single-file Blade template replacement. The new template is a standalone HTML document (like the current one) that loads CSS/JS via Vite, uses the existing `<x-application-logo>` component, and conditionally shows Register/Login or Dashboard via `@auth`/`@guest` directives.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Replace Welcome Page | New centered-card welcome template | Minimal — visual-only change, no backend impact |

**Prerequisites:** F-01 (auth scaffold) — already completed
**Estimated effort:** ~1 session, single phase

## Open Risks & Assumptions

- The `<x-application-logo>` is currently the Laravel pyramid SVG — the page will look "un-branded" until S-05 lands. This is accepted.
- Assumes Figtree font (from guest layout) is the correct font choice; S-05 may change this later.

## Success Criteria (Summary)

- Guest users see a clear, branded landing page with working Register and Log in buttons
- Authenticated users see a Dashboard link instead of auth buttons
- No trace of default Laravel scaffold content remains
