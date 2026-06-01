# Logo PNG Swap Implementation Plan

## Overview

Replace the inline SVG in the `<x-application-logo>` Blade component with an `<img>` tag pointing to `resources/images/logo.png`. The PNG asset already exists in the repo.

## Current State Analysis

- `resources/views/components/application-logo.blade.php` renders an inline SVG leaf icon
- `resources/images/logo.png` exists (GardenLog wordmark with leaf accent) but is unused
- The component is consumed in 3 places:
  - `resources/views/welcome.blade.php:18` — `class="w-20 h-20 fill-current text-gray-500"`
  - `resources/views/layouts/guest.blade.php:21` — `class="w-16 h-16 text-green-600"`
  - `resources/views/layouts/navigation.blade.php:9` — `class="block h-8 w-auto text-green-600"`

### Key Discoveries:

- The SVG uses `fill="currentColor"` and consumers pass `text-*` color classes — these won't apply to an `<img>` tag, so the color classes become irrelevant (the PNG has its own colors baked in)
- The sizing classes (`w-20 h-20`, `w-16 h-16`, `h-8 w-auto`) will still work on `<img>`
- Vite handles asset URLs via `Vite::asset()` helper

## Desired End State

The GardenLog PNG wordmark logo appears on the welcome page, auth pages (login/register), and the navigation bar. The SVG leaf icon is fully replaced.

## What We're NOT Doing

- Redesigning the welcome page layout
- Changing button styles or fixing submit buttons (confirmed working)
- Creating different logo sizes or formats
- Adding dark-mode logo variants

## Implementation Approach

Single-file change: replace the SVG markup in `application-logo.blade.php` with an `<img>` tag. Then clean up color-related classes (`fill-current`, `text-gray-500`, `text-green-600`) from the 3 consumer views since they have no effect on an `<img>`.

## Phase 1: Swap logo component and clean up consumers

### Overview

Replace SVG with PNG in the component, remove stale color classes from consumers.

### Changes Required:

#### 1. Logo component

**File**: `resources/views/components/application-logo.blade.php`

**Intent**: Replace the inline SVG with an `<img>` tag pointing to the Vite-managed PNG asset. Merge `$attributes` onto the `<img>` so consumers can still pass sizing classes.

**Contract**: `<img src="{{ Vite::asset('resources/images/logo.png') }}" alt="GardenLog" {{ $attributes }} />`

#### 2. Welcome page — remove stale color class

**File**: `resources/views/welcome.blade.php`

**Intent**: Remove `fill-current text-gray-500` from the logo usage since they don't apply to `<img>`.

**Contract**: Keep sizing classes (`w-20 h-20`), drop color classes.

#### 3. Guest layout — remove stale color class

**File**: `resources/views/layouts/guest.blade.php`

**Intent**: Remove `text-green-600` from the logo usage.

**Contract**: Keep sizing classes (`w-16 h-16`), drop color class.

#### 4. Navigation — remove stale color class

**File**: `resources/views/layouts/navigation.blade.php`

**Intent**: Remove `text-green-600` from the logo usage.

**Contract**: Keep sizing classes (`block h-8 w-auto`), drop color class.

### Success Criteria:

#### Automated Verification:

- App compiles without errors: `npm run build`
- No broken Blade syntax: `php artisan view:cache`

#### Manual Verification:

- PNG logo displays on the welcome page (/)
- PNG logo displays on the login page (/login)
- PNG logo displays on the register page (/register)
- PNG logo displays in the navigation bar (after login)
- Logo is appropriately sized in all 3 locations

**Implementation Note**: Single phase — no pause needed.

## Testing Strategy

### Manual Testing Steps:

1. Visit `/` — confirm PNG wordmark logo appears instead of SVG leaf
2. Visit `/login` — confirm PNG logo above the form
3. Visit `/register` — confirm PNG logo above the form
4. Log in → confirm PNG logo in the top navigation bar
5. Resize browser to mobile width → confirm logo doesn't overflow

## References

- Source file: `context/proposed-changes/changes.md` (item #1)
- PNG asset: `resources/images/logo.png`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands.

### Phase 1: Swap logo component and clean up consumers

#### Automated

- [ ] 1.1 App compiles without errors (`npm run build`)
- [ ] 1.2 No broken Blade syntax (`php artisan view:cache`)

#### Manual

- [ ] 1.3 PNG logo displays on welcome page
- [ ] 1.4 PNG logo displays on login page
- [ ] 1.5 PNG logo displays on register page
- [ ] 1.6 PNG logo displays in navigation bar
- [ ] 1.7 Logo is appropriately sized in all locations
