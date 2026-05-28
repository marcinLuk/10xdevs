# GardenLog Branding & Green Theme — Plan Brief

> Full plan: `context/changes/branding-nav/plan.md`

## What & Why

Replace all default Laravel/Breeze branding with GardenLog identity — an inline SVG leaf icon + "GardenLog" text mark and a cohesive green/earth-tone color palette across all interactive elements. The app currently looks like a stock Laravel project; this change gives it a visual identity that matches its garden-task-logging purpose.

## Starting Point

The app uses unmodified Laravel Breeze scaffolding: the geometric "L" logo, indigo accent colors on active nav links and focus rings, gray-800 primary buttons, and `APP_NAME=Laravel` in the page title. The AI search input (`ai-search.blade.php`) already uses green focus states, confirming green as the emerging accent.

## Desired End State

Every page shows a green leaf icon + "GardenLog" text in the nav bar (authenticated) or above the form (guest). All buttons, inputs, nav links, and focus rings use green instead of indigo/gray-800. The browser tab reads "GardenLog". The visual identity feels intentionally garden-themed.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) |
| --- | --- | --- |
| Logo approach | Inline SVG leaf + text mark | No external assets, renders instantly, themeable with Tailwind colors. |
| Color palette | Green/earth tones (Tailwind green-500/600/700) | Immediately communicates "garden" and aligns with existing green usage in AI search. |
| Welcome page | Keep as-is | Smaller scope — defer landing page to a separate change. |
| Nav links | Keep "Dashboard" only | Matches current single-route reality; no dead links. |
| Theme depth | Full green sweep | Cohesive look — indigo buttons next to green nav would feel inconsistent. |

## Scope

**In scope:**
- Application logo component (SVG replacement)
- Navigation bar (text mark + green active state)
- Guest layout (logo + text)
- Primary button, secondary button, text input, nav-link, responsive-nav-link (indigo → green)
- APP_NAME in `.env`

**Out of scope:**
- Welcome/landing page
- Dark mode theming
- Custom fonts or CSS variables
- Danger button (red stays)

## Architecture / Approach

Pure visual change across 7 Blade component files + 1 `.env` value. No new files, routes, models, or logic. All changes are Tailwind class swaps and SVG replacement. The leaf SVG uses `currentColor` so callers control color via text utility classes.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Brand Identity & Green Theme | Full rebrand: logo, colors, APP_NAME | Missed indigo reference leaving an inconsistent spot |

**Prerequisites:** None — purely visual, no dependencies.
**Estimated effort:** ~1 session, single phase.

## Open Risks & Assumptions

- The leaf SVG needs to look recognizable at both `h-8` (nav) and `w-16 h-16` (guest layout) sizes
- Dark mode classes on some components are not being updated — dark mode may look inconsistent

## Success Criteria (Summary)

- Every page shows green-themed GardenLog branding with no remaining indigo or default Laravel artifacts
- Browser tab title reads "GardenLog"
- All existing tests pass unchanged
