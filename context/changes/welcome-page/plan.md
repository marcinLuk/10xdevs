# Welcome Page Implementation Plan

## Overview

Replace the default Laravel 13 welcome page (`resources/views/welcome.blade.php`) with a clean, light-themed GardenLog landing page. The new page features a single centered card containing the existing application logo component, a welcome headline with descriptive subtext, and Register/Log in buttons — removing all Laravel marketing copy.

## Current State Analysis

The current welcome page is 224 lines of default Laravel scaffold (`resources/views/welcome.blade.php`). It includes:

- Inline Tailwind CSS v4.0.7 (not loaded via Vite)
- A two-column layout with Laravel marketing copy on the left and an animated "13" SVG logo on the right
- Conditional auth navigation links in a top-right header (Login / Register / Dashboard)
- Dark mode support via `prefers-color-scheme` media queries
- Custom hex color palette and "Instrument Sans" font

### Key Discoveries:

- The route is a simple closure: `Route::get('/', fn() => view('welcome'))` — no controller needed (`routes/web.php:5`)
- `resources/views/components/application-logo.blade.php` contains the existing SVG logo component (currently the Laravel pyramid). S-05 (branding-nav) will update this — we just consume it as-is.
- The guest layout (`resources/views/layouts/guest.blade.php`) loads Vite assets and uses `font-sans` with Figtree font — the welcome page should match this pattern instead of inlining its own CSS.
- Auth pages (login/register) use a white card on gray background pattern from the guest layout.

## Desired End State

The root URL (`/`) displays a minimal, light-themed landing page with:

1. A white background filling the viewport
2. A single centered card containing:
   - The `<x-application-logo>` component (rendered large)
   - "Welcome to GardenLog" headline
   - "Track your garden tasks and ask AI about your growing history." subtext
   - Two buttons: "Register" and "Log in"
3. Auth-conditional behavior preserved: authenticated users see a "Dashboard" link instead of (or in addition to) Register/Login
4. No dark mode — light only
5. No Laravel marketing copy, no animated SVG, no inline CSS
6. Vite-bundled assets (`@vite`) instead of inline Tailwind

When visited, the page loads fast, looks intentional (not a default scaffold), and clearly communicates what the app does.

## What We're NOT Doing

- **No new logo or brand identity** — S-05 (branding-nav) owns that; we use the existing `<x-application-logo>` as-is
- **No dark mode** — roadmap specifies "light-themed"; dark mode is out of scope
- **No route changes** — the closure route stays as-is
- **No redirect for authenticated users** — current conditional behavior is preserved
- **No backend changes** — purely a Blade template replacement
- **No new components** — reuse existing Blade components (`x-application-logo`)

## Implementation Approach

Single-phase replacement of the welcome Blade template. The new template uses the Vite asset pipeline (like the guest layout does) instead of inline CSS, keeps the page self-contained (no layout extension needed), and reuses the existing application-logo component.

## Phase 1: Replace Welcome Page

### Overview

Replace the entire `welcome.blade.php` with a clean, centered-card layout that loads assets via Vite, displays the application logo, welcome copy, and auth action buttons.

### Changes Required:

#### 1. Welcome Page Template

**File**: `resources/views/welcome.blade.php`

**Intent**: Replace the 224-line default Laravel scaffold with a minimal, light-themed landing page. The page should be a standalone HTML document (like the current one — not extending a layout) that loads CSS/JS via `@vite`, centers a single card vertically and horizontally, and contains the logo, headline, subtext, and auth buttons.

**Contract**:
- Standalone `<!DOCTYPE html>` document (same pattern as current welcome.blade.php and guest.blade.php)
- Loads assets via `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- Uses Figtree font (same as guest layout) via Bunny Fonts CDN
- Body: `bg-white min-h-screen flex items-center justify-center`
- Single centered card (max-width ~md, padding, optional subtle shadow or border)
- Card contents top-to-bottom:
  - `<x-application-logo>` rendered at a prominent size (e.g., `w-20 h-20` similar to guest layout, `fill-current text-gray-500`)
  - `<h1>` "Welcome to GardenLog" — large, bold
  - `<p>` "Track your garden tasks and ask AI about your growing history." — muted text
  - Auth buttons section using `@auth` / `@guest` directives:
    - Guest: "Register" (primary/filled button) + "Log in" (secondary/outlined or text link) — linking to `route('register')` and `route('login')`
    - Authenticated: "Dashboard" button/link — linking to `route('dashboard')`
- No dark mode classes or media queries
- Page `<title>`: app name via `{{ config('app.name', 'Laravel') }}`

### Success Criteria:

#### Automated Verification:

- `npm run build` completes without errors (Vite assets compile)
- `php artisan route:list` still shows `/` route
- `./vendor/bin/pint` passes with no style violations on the changed file

#### Manual Verification:

- Visiting `/` as a guest shows the centered card with logo, headline, subtext, Register and Log in buttons
- Register button navigates to `/register`
- Log in button navigates to `/login`
- Visiting `/` as an authenticated user shows a Dashboard button/link instead of Register/Login
- Dashboard link navigates to `/dashboard`
- Page has a white background, no dark mode artifacts
- No Laravel marketing copy or Laravel logo visible
- Page is visually centered on desktop viewport
- Page looks reasonable on narrow viewports (responsive, card doesn't overflow)

**Implementation Note**: This is a single-phase plan. After completing automated verification, pause for manual confirmation from the human that the page looks and behaves correctly.

---

## Testing Strategy

### Unit Tests:

No unit tests needed — this is a pure template change with no backend logic.

### Integration Tests:

- Existing Laravel Breeze tests should continue passing (auth routes unchanged)
- The welcome page route (`GET /`) should return 200

### Manual Testing Steps:

1. Visit `/` while logged out — verify centered card with logo, "Welcome to GardenLog" headline, subtext, Register and Log in buttons
2. Click Register — verify navigation to `/register`
3. Click Log in — verify navigation to `/login`
4. Log in and visit `/` — verify Dashboard link appears instead of Register/Login
5. Click Dashboard — verify navigation to `/dashboard`
6. Resize browser window to mobile width — verify card remains usable and doesn't overflow

## Performance Considerations

- Removing 200+ lines of inline CSS and replacing with Vite-bundled assets should improve cacheability
- The page is static Blade with no database queries — performance is inherently fast
- Single font load (Figtree) matches the rest of the app

## References

- Roadmap: S-06 in `context/foundation/roadmap.md`
- Current welcome page: `resources/views/welcome.blade.php`
- Logo component: `resources/views/components/application-logo.blade.php`
- Guest layout (style reference): `resources/views/layouts/guest.blade.php`
- Auth routes: `routes/auth.php` (via Laravel Breeze)

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Replace Welcome Page

#### Automated

- [x] 1.1 `npm run build` completes without errors — 70c1093
- [x] 1.2 `php artisan route:list` still shows `/` route — 70c1093
- [x] 1.3 `./vendor/bin/pint` passes with no style violations — 70c1093

#### Manual

- [ ] 1.4 Guest sees centered card with logo, headline, subtext, Register and Log in buttons
- [ ] 1.5 Register and Log in buttons navigate correctly
- [ ] 1.6 Authenticated user sees Dashboard link instead of auth buttons
- [ ] 1.7 Dashboard link navigates to `/dashboard`
- [ ] 1.8 White background, no dark mode artifacts, no Laravel marketing copy
- [ ] 1.9 Page looks reasonable on narrow viewports
