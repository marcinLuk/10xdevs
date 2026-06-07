# Task Edit & Delete — Plan Brief

> Full plan: `context/changes/task-edit-delete/plan.md`

## What & Why

Add edit and delete functionality to garden tasks (PRD FR-007, FR-008). Currently users can only create tasks — they
have no way to correct mistakes or remove entries. This is a core CRUD gap that blocks the app from feeling complete.

## Starting Point

The Task model, migration, controller (`index`/`store`), and `StoreTaskRequest` already exist. The dashboard renders
tasks as read-only cards. The `<x-modal>` component and Alpine.js patterns are established in the add-task-form partial.
No edit/update/destroy routes or methods exist yet.

## Desired End State

Each task card shows inline edit (pencil) and delete (trash) icon buttons. Editing opens a pre-filled modal; deleting
shows a confirmation modal. A `TaskPolicy` enforces ownership. Feature tests cover all new operations. The dashboard UX
is consistent — add, edit, and delete all use modals.

## Key Decisions Made

| Decision            | Choice                   | Why (1 sentence)                                                        |
|---------------------|--------------------------|-------------------------------------------------------------------------|
| Action button style | Inline icon buttons      | Always visible, fast access — avoids extra clicks from a dropdown menu. |
| Delete UX           | Confirmation modal       | Aligns with PRD guardrail "task data must never be silently lost."      |
| Edit UX             | Modal (same as Add Task) | Consistent UX pattern, user stays on dashboard.                         |
| Authorization       | Formal TaskPolicy class  | Laravel-idiomatic, explicit ownership check beyond query scoping.       |

## Scope

**In scope:** Edit task (description, date, type), delete task with confirmation, TaskPolicy, UpdateTaskRequest, feature
tests for all new operations.

**Out of scope:** Soft deletes / undo, bulk operations, separate edit page, sort/filter changes.

## Architecture / Approach

Standard Laravel resource pattern: add `update`/`destroy` to `TaskController`, register PUT/DELETE routes, create
`UpdateTaskRequest` mirroring `StoreTaskRequest`, and a `TaskPolicy` for ownership. Frontend adds two new Blade
partials (edit modal, delete modal) with Alpine.js wiring to pass per-task data into modals.

## Phases at a Glance

| Phase       | What it delivers                                 | Key risk                                |
|-------------|--------------------------------------------------|-----------------------------------------|
| 1. Backend  | Policy, routes, controller methods, form request | Minimal — standard Laravel CRUD         |
| 2. Frontend | Edit/delete modals, inline action buttons        | Alpine.js wiring for dynamic modal data |
| 3. Tests    | Feature tests for update/delete + authorization  | None — straightforward test additions   |

**Prerequisites:** task-log-core (S-01) is implemented.
**Estimated effort:** ~1 session across 3 phases.

## Open Risks & Assumptions

- Assumes `docker exec` workflow is active (per lessons.md)
- No risk of data model changes — existing `tasks` table is unchanged

## Success Criteria (Summary)

- User can edit any field of a saved task via modal and see it persist
- User can delete a task after confirmation and see it removed
- Another user's tasks are inaccessible (403)
