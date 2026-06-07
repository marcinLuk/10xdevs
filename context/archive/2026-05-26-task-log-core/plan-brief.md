# Task Log Core — Plan Brief

> Full plan: `context/changes/task-log-core/plan.md`

## What & Why

Build the core task logging feature (roadmap S-01) so gardeners can add tasks with a description, date, and optional type tag, then view their full history in a scrollable chronological list. This is the data foundation for the AI recall loop (S-02) — without real task data, the AI has nothing to query against.

## Starting Point

Auth scaffold (F-01) is complete: registration, login, logout, `auth` middleware, User model, Breeze Blade layouts with Alpine.js, and 13 reusable Blade components. The dashboard is a placeholder greeting. No domain models or tables exist yet — only default Laravel tables (users, sessions, cache, jobs).

## Desired End State

A logged-in gardener lands on their dashboard and sees a paginated list of their tasks (newest first). They click "Add Task" to open a modal, enter a description, pick a date (defaults to today), optionally select a type tag (watering/fertilizing/planting or custom text), and submit. The task appears in the list immediately with a flash confirmation. Each user sees only their own tasks.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) |
| --- | --- | --- |
| Task list location | Replace dashboard content | Zero navigation friction — the core feature is front and center |
| Date input | User-selectable, defaults to today | Covers real-time logging and backdating forgotten tasks |
| Type tag UI | Dropdown with presets + custom option | Quick selection for common types; custom text for anything else |
| Type tag storage | Single nullable string column | Simplest schema; no joins; easy AI recall queries |
| Pagination | Offset pagination, 15 per page | Built into Laravel; sufficient for personal-scale data |
| Add task UX | Modal overlay (Alpine.js) | Keeps list visible; leverages existing Breeze `x-modal` component |
| Validation | Pragmatic constraints | Required description (max 500), date not-future, type optional (max 100) |
| Type display in list | Plain text label only | FR-011 colour/icon is nice-to-have, deferred per roadmap |
| Testing | Feature tests (HTTP) + model unit test | Matches existing Pest patterns; covers full request lifecycle |
| Success feedback | Flash message + list update | Clear confirmation following Laravel session flash convention |
| Future dates | Rejected by validation | PRD excludes scheduled tasks; prevents accidental typos |

## Scope

**In scope:** Task model + migration, TaskController (index + store), StoreTaskRequest, Blade views (list + modal), pagination, flash messages, Pest tests

**Out of scope:** Edit/delete tasks (S-03), AI search (S-02), colour/icon by type (FR-011), week-view calendar (FR-012), API routes

## Architecture / Approach

Standard Laravel resource pattern: migration → model → form request → controller → routes → Blade views → tests. The `tasks` table has a foreign key to `users` with a composite index on `(user_id, task_date)` for efficient chronological queries. The add-task modal reuses the existing Breeze `x-modal` component and Alpine.js event dispatch pattern. No new JS dependencies.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Data Layer | tasks migration, Task model, factory | None — standard Eloquent pattern |
| 2. Backend Logic | TaskController, StoreTaskRequest, routes | Dashboard route change may affect existing tests |
| 3. Frontend Views | Task list, add-task modal, pagination, flash | Custom type toggle in dropdown needs Alpine.js logic |
| 4. Testing | Feature + unit tests for full lifecycle | None — follows established Pest patterns |

**Prerequisites:** Auth scaffold (F-01) complete and merged
**Estimated effort:** ~1-2 sessions across 4 phases

## Open Risks & Assumptions

- The `x-modal` component's `focusable` attribute should auto-focus the first form field — needs manual verification
- Custom type toggle (Alpine.js `x-show`) is a small addition but adds interactivity not yet tested by Pest — manual browser testing required

## Success Criteria (Summary)

- A gardener can add a task with description + date + optional type and see it in the chronological list
- Tasks are scoped to the authenticated user — cross-user isolation verified by tests
- Pagination works at 15 tasks/page with correct ordering (newest first)
