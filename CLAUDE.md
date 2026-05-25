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

## 10xDevs AI Toolkit - Module 2, Lesson 1

Move from sprint-zero setup to project orchestration with the **roadmap chain**:

```
(Module 1 foundation docs) -> /10x-roadmap -> backlog-ready roadmap items
```

`/10x-roadmap` is the lesson focus. `/10x-new` is intentionally introduced in Module 2, Lesson 2, when a selected roadmap item becomes an implementation change folder.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Roadmap (lesson focus)** | |
| `/10x-roadmap` | You have `context/foundation/prd.md` and a scaffolded project baseline, and you need a vertical-first MVP roadmap. The skill reads the PRD, inspects the code baseline, uses available foundation docs such as `tech-stack.md`, `infrastructure.md`, and `deploy-plan.md`, then writes `context/foundation/roadmap.md`. Use it BEFORE creating per-change folders or implementation plans. |
| **Re-run upstream if needed** | |
| `/10x-shape` / `/10x-prd` / `/10x-tech-stack-selector` / `/10x-bootstrapper` / `/10x-agents-md` / `/10x-infra-research` | Bundled from Module 1 so foundation contracts can be fixed before roadmap sequencing. If roadmap generation exposes a PRD gap, repair the PRD before pretending the backlog is ready. |

### How the chain hands off

- `/10x-roadmap` bridges product and implementation. It does not choose frameworks, design schemas, or write a per-change implementation plan.
- The output is `context/foundation/roadmap.md`: ordered milestones, vertical slices, bounded foundations, dependencies, unknowns, risk, and backlog handoff fields.
- Roadmap items should receive stable human-readable identifiers in backlog tools. The actual `context/changes/<change-id>/` folder is created in Lesson 2 with `/10x-new`.

### Roadmap boundaries

- Default to vertical slices: user-visible outcomes that cross UI, data, business logic, and integrations.
- Horizontal work is allowed only as a bounded enabler that names the downstream vertical milestone it unlocks.
- Avoid orphan horizontal work such as "build the whole database", "build all API endpoints", or "design the whole UI" before the first user-visible flow.
- Roadmap is not a calendar estimate. Do not invent dates, story points, or sprint velocity unless the user explicitly asks for a separate planning artifact.

### Foundation paths used by this lesson

- `context/foundation/prd.md` - input
- `context/foundation/tech-stack.md` - optional input
- `context/foundation/infrastructure.md` - optional input
- `context/deployment/deploy-plan.md` - optional input
- `context/foundation/roadmap.md` - output
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
