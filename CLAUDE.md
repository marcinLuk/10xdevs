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

## 10xDevs AI Toolkit - Module 2, Lesson 2

Turn one roadmap item into the first implementation cycle with the **change planning chain**:

```
/10x-roadmap -> /10x-new -> /10x-plan -> /10x-plan-review -> /10x-implement
```

`/10x-new`, `/10x-plan`, `/10x-plan-review`, and `/10x-implement` are the lesson focus. `/10x-frame` and `/10x-research` are not required rituals here; they are escalation paths introduced in the next lesson.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Change setup (lesson focus)** | |
| `/10x-new <change-id>` | You selected a roadmap item and need a stable change folder. Creates `context/changes/<change-id>/change.md` so planning, implementation, progress, commits, and later review all share one identity. Use AFTER roadmap selection, BEFORE `/10x-plan`. |
| **Planning (lesson focus)** | |
| `/10x-plan <change-id>` | You have a change folder and need a reviewable implementation plan. Reads roadmap context, foundation docs, codebase evidence, and any existing change notes; writes `plan.md` and `plan-brief.md` with phases, file contracts, success criteria, and `## Progress`. |
| **Plan readiness (lesson focus)** | |
| `/10x-plan-review <change-id>` | You have `plan.md` and need a light pre-code readiness check. Use it to catch missing end state, weak contracts, malformed progress, scope drift, or blind spots before code changes begin. |
| **Implementation (lesson focus)** | |
| `/10x-implement <change-id> phase <n>` | You have an approved plan and want to execute one phase with verification, manual gate, commit ritual, and SHA write-back to `## Progress`. |
| **Lifecycle closure** | |
| `/10x-archive <change-id>` | A change is merged or intentionally closed. Move it out of active `context/changes/` into archive state. |

### How the chain hands off

- `/10x-new` creates the durable change identity.
- `/10x-plan` turns that identity into an implementation contract.
- `/10x-plan-review` checks the plan before the agent mutates code.
- `/10x-implement` executes one planned phase, verifies, asks for manual confirmation when needed, commits, and records progress.

### Lesson boundaries

- Plan is the default router after roadmap selection. Start with `/10x-plan` unless the problem is unclear or external evidence is blocking.
- Do not run `/10x-frame + /10x-research` as ceremony for every change.
- Do not turn this lesson into a full end-to-end product build. A checkpoint with a planned and partially or fully implemented stream is valid.
- Code review of the implemented diff belongs to Lesson 3 via `/10x-impl-review`.
- Lifecycle closure via `/10x-archive` after a change is merged or intentionally closed.

### Paths used by this lesson

- `context/foundation/roadmap.md` - upstream roadmap
- `context/changes/<change-id>/change.md` - change identity
- `context/changes/<change-id>/plan.md` - implementation contract
- `context/changes/<change-id>/plan-brief.md` - compressed handoff
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
