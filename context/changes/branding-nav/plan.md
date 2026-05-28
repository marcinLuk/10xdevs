# GardenLog Branding & Green Theme Implementation Plan

## Overview

Replace all default Laravel/Breeze branding with GardenLog identity: an inline SVG leaf icon paired with "GardenLog" text mark, and a cohesive green/earth-tone color palette applied across navigation, buttons, inputs, and focus states. The welcome page is explicitly out of scope.

## Current State Analysis

The app ships with stock Laravel Breeze visuals:

- **Logo**: `resources/views/components/application-logo.blade.php` renders the default Laravel geometric "L" SVG — no garden identity.
- **Navigation**: `resources/views/layouts/navigation.blade.php` uses `<x-application-logo>` with no app name text. Active nav link uses indigo (`border-indigo-400`).
- **Guest layout**: `resources/views/layouts/guest.blade.php` shows the same Laravel logo on login/register.
- **Primary button**: `resources/views/components/primary-button.blade.php` — `bg-gray-800` with `focus:ring-indigo-500`.
- **Secondary button**: `resources/views/components/secondary-button.blade.php` — `focus:ring-indigo-500`.
- **Text input**: `resources/views/components/text-input.blade.php` — `focus:border-indigo-500 focus:ring-indigo-500`.
- **Responsive nav link**: `resources/views/components/responsive-nav-link.blade.php` — active state uses indigo across border, text, and background.
- **Nav link**: `resources/views/components/nav-link.blade.php` — active state uses `border-indigo-400`, focus uses `focus:border-indigo-700`.
- **APP_NAME**: `.env` has `APP_NAME=Laravel` — page titles show "Laravel".
- **AI search input**: `resources/views/tasks/partials/ai-search.blade.php` already uses `focus:border-green-500 focus:ring-green-500` — partial green adoption already exists.

### Key Discoveries:

- `application-logo.blade.php:1` — component accepts `$attributes` and merges them, so size/color classes from callers will carry over to the replacement
- `navigation.blade.php:8-11` — logo links to `route('dashboard')`, wrapped in a `shrink-0 flex items-center` div
- `guest.blade.php:20-22` — logo links to `/`, sized `w-20 h-20`
- `app.css:1-3` — only contains Tailwind directives; all theming is done via inline Tailwind classes
- The AI search partial already uses green focus colors, confirming green as the emerging accent

## Desired End State

After this plan is complete:

- The app shows a leaf SVG icon + "GardenLog" text mark in the navigation bar and on login/register pages.
- All interactive elements (buttons, inputs, active nav states, focus rings) use green tones instead of indigo/gray-800.
- Page titles show "GardenLog" instead of "Laravel".
- The visual identity feels cohesive and garden-themed throughout the authenticated and guest experiences.

**Verification**: Open the app, navigate through login, register, dashboard, and profile pages — every page should show green-themed branding with no remaining indigo or default Laravel visual artifacts.

## What We're NOT Doing

- Welcome/landing page (`welcome.blade.php`) — explicitly deferred per user decision
- Custom fonts or typography changes — staying with Figtree
- Dark mode theming — the current Breeze setup has dark mode classes on some components; we won't touch those
- Danger button colors — red is semantically correct and stays
- Custom CSS variables or `tailwind.config.js` — all changes via Tailwind utility classes

## Implementation Approach

Single-phase change touching 7 Blade component files + 1 `.env` value. All changes are independent color/SVG swaps with no logic changes. The phase is structured to minimize risk: logo first (most visible), then color sweep across components.

## Phase 1: Brand Identity & Green Theme

### Overview

Replace the Laravel logo with a GardenLog leaf + text mark, update APP_NAME, and sweep all indigo references to green equivalents across Blade components.

### Changes Required:

#### 1. Application Logo Component

**File**: `resources/views/components/application-logo.blade.php`

**Intent**: Replace the Laravel geometric SVG with a simple leaf icon. The component must continue to accept and merge `$attributes` so callers can control size and color.

**Contract**: The component renders an `<svg>` element that merges `{{ $attributes }}`. Callers pass `class="..."` to control dimensions. The new SVG should be a recognizable leaf shape using `currentColor` for fill so it inherits text color from the parent context.

#### 2. Navigation Bar — Add Text Mark

**File**: `resources/views/layouts/navigation.blade.php`

**Intent**: Add "GardenLog" text next to the logo icon in the nav bar so the app name is always visible. Keep the logo linking to `route('dashboard')`.

**Contract**: Inside the existing `shrink-0 flex items-center` div (line 7), after the `<x-application-logo>` tag, add a `<span>` with the text "GardenLog" styled with `font-semibold text-lg text-green-800`. Adjust logo size class to `h-8 w-auto text-green-600` for the nav context.

#### 3. Guest Layout — Garden Logo

**File**: `resources/views/layouts/guest.blade.php`

**Intent**: Update the logo on login/register pages to show the green-themed leaf icon at the existing size, keeping the link to `/`.

**Contract**: The `<x-application-logo>` tag (line 21) changes its class from `w-20 h-20 fill-current text-gray-500` to `w-16 h-16 text-green-600`. Add "GardenLog" text below the logo as a styled heading.

#### 4. Nav Link Component — Green Active State

**File**: `resources/views/components/nav-link.blade.php`

**Intent**: Replace indigo active/focus states with green equivalents for visual consistency.

**Contract**: In the active class string, replace `border-indigo-400` → `border-green-500`, `focus:border-indigo-700` → `focus:border-green-700`. In the inactive class string, no indigo references exist (uses gray), so no changes needed.

#### 5. Responsive Nav Link Component — Green Active State

**File**: `resources/views/components/responsive-nav-link.blade.php`

**Intent**: Replace all indigo references in the active state with green equivalents for the mobile nav.

**Contract**: In the active class string: `border-indigo-400` → `border-green-500`, `text-indigo-700` → `text-green-700`, `bg-indigo-50` → `bg-green-50`, `focus:text-indigo-800` → `focus:text-green-800`, `focus:bg-indigo-100` → `focus:bg-green-100`, `focus:border-indigo-700` → `focus:border-green-700`.

#### 6. Primary Button — Green Background

**File**: `resources/views/components/primary-button.blade.php`

**Intent**: Change the primary button from gray-800 to green so it matches the garden theme.

**Contract**: Replace `bg-gray-800` → `bg-green-600`, `hover:bg-gray-700` → `hover:bg-green-700`, `focus:bg-gray-700` → `focus:bg-green-700`, `active:bg-gray-900` → `active:bg-green-800`, `focus:ring-indigo-500` → `focus:ring-green-500`.

#### 7. Secondary Button — Green Focus Ring

**File**: `resources/views/components/secondary-button.blade.php`

**Intent**: Replace the indigo focus ring with green for consistency.

**Contract**: Replace `focus:ring-indigo-500` → `focus:ring-green-500`.

#### 8. Text Input — Green Focus States

**File**: `resources/views/components/text-input.blade.php`

**Intent**: Replace indigo focus states with green (matching the AI search input that already uses green).

**Contract**: Replace `focus:border-indigo-500` → `focus:border-green-500`, `focus:ring-indigo-500` → `focus:ring-green-500`.

#### 9. APP_NAME Environment Variable

**File**: `.env`

**Intent**: Update the application name so page titles and any `config('app.name')` references show "GardenLog" instead of "Laravel".

**Contract**: Change `APP_NAME=Laravel` → `APP_NAME=GardenLog`.

### Success Criteria:

#### Automated Verification:

- All tests pass: `docker exec gardenlog-app php artisan test`
- Code style passes: `docker exec gardenlog-app ./vendor/bin/pint --test`
- No remaining `indigo` references in `resources/views/components/` Blade files: `grep -r "indigo" resources/views/components/`
- No remaining default Laravel SVG path in `application-logo.blade.php`

#### Manual Verification:

- Login page shows leaf icon + "GardenLog" text with green color scheme
- Register page shows the same branding
- Dashboard nav bar shows leaf icon + "GardenLog" text mark on the left
- Active "Dashboard" nav link has green underline (not indigo)
- "Add Task" button is green
- All text inputs show green focus ring when clicked
- Mobile hamburger menu shows green active state for "Dashboard"
- Page title in browser tab shows "GardenLog" (not "Laravel")
- AI search input focus ring still works correctly (already green)
- Profile page buttons and inputs use green theme

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Testing Strategy

### Unit Tests:

- Existing test suite should pass unchanged — no logic modifications in this change
- No new tests needed — this is a purely visual change

### Integration Tests:

- Existing feature tests (login, register, task CRUD, AI search) exercise the views and will catch any broken template syntax

### Manual Testing Steps:

1. Visit `/login` — verify leaf logo, "GardenLog" text, green-themed inputs
2. Visit `/register` — same branding check
3. Log in — verify nav bar shows leaf + "GardenLog", active link is green-underlined
4. Click "Add Task" — verify green button
5. Click into any text input — verify green focus ring
6. Resize browser to mobile width — verify hamburger menu shows green active state
7. Check browser tab title — should show "GardenLog"
8. Visit `/profile` — verify green theme on buttons and inputs there too

## Performance Considerations

None — this is a CSS class swap and SVG replacement. No new assets, no new HTTP requests, no bundle size change.

## References

- Current navigation: `resources/views/layouts/navigation.blade.php`
- Current logo: `resources/views/components/application-logo.blade.php`
- AI search (already green): `resources/views/tasks/partials/ai-search.blade.php:59`
- PRD: `context/foundation/prd.md`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Brand Identity & Green Theme

#### Automated

- [ ] 1.1 All tests pass
- [ ] 1.2 Code style passes (Pint)
- [ ] 1.3 No remaining indigo references in Blade components
- [ ] 1.4 No default Laravel SVG in application-logo

#### Manual

- [ ] 1.5 Login/register pages show GardenLog branding
- [ ] 1.6 Nav bar shows leaf icon + "GardenLog" text
- [ ] 1.7 All interactive elements use green theme
- [ ] 1.8 Browser tab title shows "GardenLog"
- [ ] 1.9 Mobile nav shows green active state
