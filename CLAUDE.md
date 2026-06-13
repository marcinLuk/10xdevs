# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project: GardenLog

An AI-powered garden task logging app built with Laravel 13 + Blade + Tailwind CSS 4. Users log garden tasks (watering, fertilizing, planting) and query their history via natural-language AI search (Claude API). See [`context/foundation/prd.md`](context/foundation/prd.md) for full requirements.

## Commands

```bash
# Full initial setup (install deps, .env, app key, migrate, npm install, build)
composer setup

# Start all dev processes (Laravel server + queue + logs + Vite) via concurrently
composer dev

# Run tests (clears config cache first, uses SQLite :memory:)
composer test

# Run a single test file or filter
php artisan test --filter=SomeTestName
php artisan test tests/Feature/ExampleTest.php

# Code style (Laravel Pint)
./vendor/bin/pint

# Frontend only
npm run dev
npm run build
```

## Artisan CLI — first-class tool for file generation

**Always prefer `php artisan make:*` over manually creating files.** Before writing any Laravel class by hand, check whether an Artisan generator exists for it.

Common generators:

| Task | Command |
| --- | --- |
| Controller | `php artisan make:controller NameController` |
| Model (+ migration) | `php artisan make:model Name -m` |
| Migration | `php artisan make:migration create_name_table` |
| Middleware | `php artisan make:middleware NameMiddleware` |
| Request (Form Request) | `php artisan make:request NameRequest` |
| Seeder | `php artisan make:seeder NameSeeder` |
| Factory | `php artisan make:factory NameFactory` |
| Job | `php artisan make:job NameJob` |
| Event / Listener | `php artisan make:event Name` / `php artisan make:listener Name` |
| Mail | `php artisan make:mail NameMail` |
| Notification | `php artisan make:notification NameNotification` |
| Policy | `php artisan make:policy NamePolicy` |
| Resource (API) | `php artisan make:resource NameResource` |
| Command | `php artisan make:command NameCommand` |
| Service Provider | `php artisan make:provider NameServiceProvider` |
| Test | `php artisan make:test NameTest` |

Run `php artisan list make` to see the full up-to-date list. When unsure, check that list before creating any file manually.

## `playwright-cli` — drive a real browser from the terminal

`playwright-cli` is installed globally and lets you control a live browser straight from the shell (no test file needed) — useful for exploring the running app, reproducing UI bugs, and capturing the accessibility snapshot you'll base E2E locators on.

```bash
playwright-cli open http://localhost:8001   # launch the browser at a url
playwright-cli snapshot                      # dump the accessibility tree (element refs)
playwright-cli click "Log in"                # interact by accessible name / ref
playwright-cli fill "Email" you@example.com
playwright-cli screenshot                    # save a screenshot of the page
playwright-cli console                       # read console messages
playwright-cli requests                      # inspect network traffic
playwright-cli close                          # close the browser
```

- Run `playwright-cli --help` (or `--help <command>`) for the full command list; add `--json` / `--raw` for scriptable output.
- The app's dev server runs on `http://localhost:8001` (see `composer dev`).
- Snapshots are written to `.playwright-cli/` (gitignored) — read those YAML dumps to find element `[ref=...]` ids and accessible names before writing locators.
- This is an exploration/debugging driver, **not** a substitute for committed Playwright E2E specs — for authoring those, use the `/10x-e2e` skill (see below).

## Architecture

**Entry points:**
- `routes/web.php` — all web routes (Blade-rendered)
- `routes/api.php` — REST API routes (not yet created)

**Data layer:**
- Default SQLite in dev (`database/database.sqlite`); MySQL 8.0 via Docker (`docker-compose.yml`, port 3307)
- Tests use SQLite `:memory:` (configured in `phpunit.xml`)
- Sessions, cache, and queue all use the `database` driver

**Frontend:**
- Blade templates in `resources/views/`
- Tailwind CSS v4 via `@tailwindcss/vite` — no `tailwind.config.js`; config lives inside `resources/css/app.css` using CSS custom properties
- Vite bundles `resources/js/app.js` and `resources/css/app.css`

**Docker:**
- `docker-compose.yml` runs app (PHP-FPM), Nginx (port 8001), and MySQL
- Use `composer dev` for local dev without Docker (uses built-in PHP server via `artisan serve`)

**Context/foundation contracts** (read before planning features):
- [`context/foundation/prd.md`](context/foundation/prd.md) — product requirements
- [`context/foundation/tech-stack.md`](context/foundation/tech-stack.md) — tech stack decisions
- [`context/foundation/shape-notes.md`](context/foundation/shape-notes.md) — scoping constraints

<!-- BEGIN @przeprogramowani/10x-cli -->

## 10xDevs AI Toolkit - Module 3, Lesson 4 (E2E Tests)

**For E2E tests, use the `/10x-e2e` skill.** It is the single source of truth
for the workflow — risk → seed test + rules → generate → review against the five
anti-patterns → re-prompt → verify. The skill's `references/` carry the full
rules, anti-patterns, seed pattern, and prompt-template.

A few hard rules that hold even before you invoke the skill:

- **Locators:** `getByRole` / `getByLabel` / `getByText` first; `getByTestId`
  only when accessibility attributes are ambiguous. Never CSS selectors, XPath,
  or DOM structure.
- **Never `page.waitForTimeout()`.** Wait for state: `toBeVisible()`,
  `waitForURL()`, `waitForResponse()`.
- **Test independence + cleanup.** Each test runs standalone — its own setup,
  action, assertion, and cleanup; unique ids (timestamp suffix) so parallel runs
  and re-runs don't collide.

Two boundaries to keep straight:

- **DOM (snapshot) is the default.** Vision (`--caps=vision`) is a supplement for
  visual-only risks (layout, z-index, animation); for pixel regression prefer
  deterministic tools (`toMatchSnapshot`, Argos, Lost Pixel). VLM model
  selection/cost is a debugging topic (Lesson 5), not testing.
- **Healer helps on selectors, harms on logic.** A changed selector → healer
  re-finds it (route through PR review). A changed business behavior → healer
  masks the bug; that failing-test-to-fix case is Lesson 5.

<!-- END @przeprogramowani/10x-cli -->
