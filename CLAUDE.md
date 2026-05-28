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

## 10xDevs AI Toolkit - Module 2, Lesson 5

Scale the single-change cycle into parallel work with **worktrees, goal-directed delegation, and multi-session orchestration**:

```
worktree per change -> /goal or claude -p -> PR -> review -> merge
```

The lesson focus is safe throughput: isolated contexts, choosing the right execution mode, and capping parallelism at review capacity.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Code isolation** | |
| `git worktree add` | You need a separate working directory for a parallel change. One change per worktree, one fresh agent context per worktree. |
| **Complex changes** | |
| `/10x-implement <change-id> phase <n>` | The change has multiple phases, needs manual gates, or benefits from interactive decision-making during execution. |
| **Simple changes** | |
| `/goal` | You have a clear, bounded task and want goal-directed delegation. The agent works autonomously toward the stated goal with a stop condition. |
| `claude -p` | You want headless execution for a well-defined task. The Ralph Wiggum loop (run, check, retry) is the universal autonomous pattern. |
| **Multi-session orchestration** | |
| Superset / Conductor / Antigravity / VS Code Agent View | You are running multiple agent sessions in parallel and need visibility, coordination, or session management across them. |

### Parallel work rules

- One change per worktree or isolated workspace. One fresh agent context per change.
- Choose interactive `/10x-implement` for complex changes, `/goal` or `claude -p` for simple ones.
- Parallelism is capped by review capacity. More agents without review means more unreviewed code, not higher throughput.
- The quality pain from faster shipping is intentional — it bridges into Module 3 testing gates.

### Lesson boundaries

- Do not reteach interactive `/10x-implement` or `/10x-impl-review`; those are Lessons 2 and 3.
- Do not introduce testing strategy here. The quality pain is the motivation for Module 3.
- Worktrees are a mechanism for isolation, not the topic of a full git tutorial.

### Paths used by this lesson

- `context/changes/<change-id>/` - active change folder
- `context/changes/<change-id>/plan.md` - implementation input for any execution mode

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
