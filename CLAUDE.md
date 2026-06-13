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

## 10xDevs AI Toolkit - Module 3, Lesson 3

Lesson 3 is about **hooks** — turning the quality gates from Lesson 1 and the tests from Lesson 2 into automatic, deterministic checks that fire while the agent works. A hook runs outside the model, so it survives context compression, instruction changes, and the model "forgetting". The payoff for agentic hooks specifically: a `PostToolUse` check can feed its result back into the agent's context, so the agent fixes trivial errors (formatting, a missing import, a wrong type) on its own in the next iteration instead of you discovering them minutes later.

```
context/foundation/test-plan.md  (§4 Quality Gates: which check, required when)
        │
        ▼  (assign each gate to the cheapest layer that still gives signal)
   per-edit (agent hooks)  →  pre-commit (git hooks)  →  pre-push  →  CI
        │ lint, format, scoped tests          │ staged       │ heavier    │ integration
        ▼
   exit code + stdout  →  additionalContext  →  agent reacts next turn
```

### Task Router — Which layer for this check

| You want to | Do this |
| --- | --- |
| React the instant the agent edits a file | A per-edit hook (`PostToolUse` matcher `Write\|Edit` in Claude Code). Right for fast checks: lint/format, and scoped tests on risk-area files. This is the **only** layer that can hand feedback to the agent mid-session. |
| Run only the tests that depend on the edited file | Parse the path from the hook's stdin (`jq -r .tool_input.file_path`) and run your runner's related-tests mode (`vitest related "$FILE" --run`, `jest --findRelatedTests $FILE`). Gate it on whether the file is a risk area in `test-plan.md`; don't run tests on every helper or config edit. |
| Catch changes that bypassed the agent (manual edits, a teammate's commit) | A pre-commit git hook (Lefthook or Husky+lint-staged) over staged files: lint + typecheck, and tests on staged risk files. |
| Run heavier checks before code leaves the machine | Pre-push: full typecheck or a broader test set. Anything too slow for per-edit moves here. |
| Decide where a given gate belongs | Ask: is it fast enough (a few seconds) for per-edit, or should it wait for commit/push/CI? Slow checks block the agent loop on every edit — push them up a layer. |
| Use the same hook across tools | The trigger → matcher → handler → signal pattern is the same in Cursor, Codex, Windsurf, and Copilot; only the config file and event names change. See the cross-tool table below. |

### Hook lifecycle — the universal pattern

Every tool's hooks follow four steps:

1. **Trigger** — an event in the tool (e.g. the agent just saved a file: `PostToolUse`).
2. **Matcher** — a filter deciding whether this hook runs (tool name like `Write`/`Edit`, file type, or a name pattern).
3. **Handler** — the action that runs, usually a shell command.
4. **Signal** — the result returns to the tool. The exit code says pass/fail; stdout can flow into the agent's context as feedback.

### Exit codes and the feedback loop

- **0** — success; the hook passed, continue.
- **2** — blocking error; the agent sees the feedback and should react.
- **anything else** — non-blocking error; logged, but does not interrupt work.

On a blocking failure, stdout flows into the agent's context (in Claude Code via `additionalContext`, capped at 10,000 characters; other tools have similar mechanisms with their own limits). That is why the agent can self-correct: it sees the concrete message — missing type, unimported module, badly formatted line — not just "something failed".

The boundary: the agent reliably fixes **trivial** corrections on its own. When a test fails because of wrong business logic, the hook surfaces it but the agent may not diagnose the real cause — it says "something is off" and tries a trivial fix. If that does not resolve in one or two tries, the signal comes back to you, and the problem may deserve its own change-id with the full `/10x-new → /10x-research → /10x-plan → /10x-implement` workflow.

### Three local layers (plus CI)

| Layer | Catches | Timing |
| --- | --- | --- |
| Per-edit (agent hooks) | Formatting, simple type errors, failing unit tests on risk files. Only layer that feeds the agent mid-work. | ms–s |
| Pre-commit (git hooks) | What slipped past per-edit: manual edits, files changed outside the hook, checks too slow for per-edit. Operates on staged files. | s |
| Pre-push | Heavier checks before pushing to remote (full typecheck, broader test set). | s–min |
| CI | Integration problems, cross-module dependencies, checks needing infra unavailable locally. | min |

Local layers do **not** replace CI — CI stays the key verification for shared repo state and environments you don't control. But each local layer that catches an error is one fewer CI round-trip. You don't need all layers from day one: start with one per-edit hook (lint) and one commit gate, add layers as you see what escapes. The quality gates in `test-plan.md §4` decide which checks are worth automating and when; a plan may legitimately defer per-edit hooks if the cost/signal ratio isn't there yet.

### Key rules

- Keep per-edit hooks fast. If a check takes more than a few seconds, move it to commit, push, or CI — a slow per-edit hook blocks the agent loop on every edit. Lint/format are ideal per-edit; full typecheck is often a commit gate in larger projects.
- Run scoped tests, not the whole suite, per edit — only tests related to the edited file, and only when that file is a risk area in `test-plan.md`.
- `related` is a subcommand, not a flag (`vitest related`, not `--related`). Use `--run` so the hook terminates instead of entering watch mode.
- `PostToolUse` fires once per tool use; three edits in one turn fire it three times independently — there is no built-in aggregation.
- The git hook tool (Lefthook vs Husky+lint-staged) is an implementation detail; the rule is the same — run checks on staged files before commit. If Husky already works, don't migrate.
- **Context injection is not universal.** Claude Code, Cursor, Codex, and Copilot (in VS Code) can pass a hook's result to the agent; Windsurf cannot — it can block (exit 2) but can't tell the agent what went wrong.

### The same pattern in every tool

| Tool | Events | Handlers | Context injection | Config |
| --- | --- | --- | --- | --- |
| Claude Code | ~30 | command, http, mcp_tool, prompt, agent | yes | `.claude/settings.json` |
| Cursor | ~18 | command, prompt | yes | `.cursor/hooks.json` |
| Codex | 10 | command | yes | `.codex/hooks.json` |
| Windsurf | 12 | command | **no** | `.windsurf/hooks.json` |
| Copilot | ~13 | command, http, prompt | yes (VS Code) | `.github/hooks/*.json` |

### Lesson boundaries

- This lesson configures hooks and local quality layers only. The hook JSON, `lefthook.yml`, and the per-edit/commit/push layering are the scope.
- Do not write E2E tests, configure Playwright/MCP, or run browser scenarios. That is Lesson 4.
- Do not run the bug-to-fix-to-regression-test debugging workflow. That is Lesson 5.
- Do not change the risk strategy or quality-gate definitions. That is Lesson 1 (`/10x-test-plan`); read current state with `/10x-test-plan --status`.
- Do not write unit/integration test code from scratch here. That is Lesson 2 — hooks only *run* the tests those lessons produced.
- Do not author CI/CD pipelines. That is Module 1 Lesson 5 / Module 2 Lesson 5; hooks are the local layers in front of CI.

### Paths used by this lesson

- `.claude/settings.json` — hook configuration (`~/.claude/settings.json` global, `.claude/settings.json` project, `.claude/settings.local.json` local overrides). Other tools use their own config file (see the table).
- `lefthook.yml` — pre-commit git hook config (lint + typecheck + tests on `{staged_files}`).
- `context/foundation/test-plan.md` — §4 quality gates decide which checks to automate and at which layer; risk areas decide which edits warrant scoped tests.

<!-- END @przeprogramowani/10x-cli -->
