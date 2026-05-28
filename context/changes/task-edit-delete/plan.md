# Task Edit & Delete Implementation Plan

## Overview

Add edit and delete functionality to garden tasks (PRD FR-007, FR-008). Users will see inline pencil/trash icon buttons on each task card. Editing opens a modal pre-filled with the task's current data (reusing the Add Task modal pattern). Deleting shows a confirmation modal before permanently removing the task. A formal TaskPolicy guards ownership.

## Current State Analysis

The Task model, migration, factory, and `StoreTaskRequest` exist. The controller has `index` and `store` methods. Routes are `GET /dashboard` and `POST /tasks`. The dashboard renders tasks as read-only cards. The `<x-modal>`, `<x-danger-button>`, and Alpine.js patterns are established in the add-task-form partial.

### Key Discoveries:

- `TaskController` at `app/Http/Controllers/TaskController.php` has only `index` and `store`
- `StoreTaskRequest` handles type normalization via `prepareForValidation()` — the update request needs the same logic
- Routes in `routes/web.php:13-16` are inside the existing `auth` middleware group — new routes go here too
- Dashboard task cards at `resources/views/dashboard.blade.php:38-51` have no action buttons
- `<x-modal>` component supports named modals opened via `$dispatch('open-modal', 'name')` — edit modal needs per-task data passed via Alpine.js
- `<x-danger-button>` component exists at `resources/views/components/danger-button.blade.php`
- Lesson: routes must go inside the existing `Route::middleware('auth')->group()` block
- Lesson: all artisan/test commands must run via `docker exec`

## Desired End State

Each task card on the dashboard shows pencil (edit) and trash (delete) icon buttons. Clicking edit opens a modal pre-filled with the task's description, date, and type. Submitting the edit updates the task and returns to the dashboard with a success flash. Clicking delete opens a confirmation modal; confirming permanently removes the task. A `TaskPolicy` ensures users can only modify their own tasks. Feature tests cover all CRUD operations, validation, authorization, and data isolation.

### Verification:

- `docker exec gardenlog-app php artisan test` passes with all new tests green
- Manual: edit a task, verify fields update; delete a task, verify it disappears; confirm another user's tasks are inaccessible

## What We're NOT Doing

- Soft deletes or undo functionality — PRD says permanent delete with confirmation is sufficient
- Bulk edit/delete — single-task operations only
- Separate edit page — all editing happens in modals on the dashboard
- Reordering or sorting changes — existing `latest('task_date')` ordering is unchanged

## Implementation Approach

Standard Laravel resource pattern: add `edit`, `update`, `destroy` to `TaskController`, register resource routes, create `UpdateTaskRequest` (mirroring `StoreTaskRequest`), and a `TaskPolicy` for ownership authorization. Frontend adds two new Blade partials (edit modal, delete modal) included in the dashboard, with Alpine.js managing per-task data flow into modals.

---

## Phase 1: Backend — Policy, Routes, Controller & Request

### Overview

Create the TaskPolicy, UpdateTaskRequest, and controller methods. Register resource routes for edit, update, and destroy.

### Changes Required:

#### 1. TaskPolicy

**File**: `app/Policies/TaskPolicy.php` (new — generate with `php artisan make:policy TaskPolicy --model=Task`)

**Intent**: Authorization gate ensuring users can only update or delete tasks they own. Register the policy so `$this->authorize()` works in the controller.

**Contract**: `update(User $user, Task $task): bool` and `delete(User $user, Task $task): bool` — both return `$user->id === $task->user_id`.

#### 2. UpdateTaskRequest

**File**: `app/Http/Requests/UpdateTaskRequest.php` (new — generate with `php artisan make:request UpdateTaskRequest`)

**Intent**: Validation for task updates. Same rules and type normalization as `StoreTaskRequest`.

**Contract**: `authorize()` returns `true` (policy handles authorization). `rules()` identical to `StoreTaskRequest`. Copy `prepareForValidation()` type normalization logic from `StoreTaskRequest`.

#### 3. TaskController — update and destroy methods

**File**: `app/Http/Controllers/TaskController.php`

**Intent**: Add `update` and `destroy` actions. Each authorizes via the policy, performs the operation, and redirects to dashboard with a success flash message.

**Contract**:
- `update(UpdateTaskRequest $request, Task $task): RedirectResponse` — calls `$this->authorize('update', $task)`, then `$task->update($request->validated())`, redirects with flash.
- `destroy(Request $request, Task $task): RedirectResponse` — calls `$this->authorize('delete', $task)`, then `$task->delete()`, redirects with flash.

#### 4. Routes

**File**: `routes/web.php`

**Intent**: Register PUT and DELETE routes for tasks inside the existing auth middleware group.

**Contract**: Add `Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update')` and `Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy')` inside the existing `Route::middleware('auth')->group()`.

### Success Criteria:

#### Automated Verification:

- `docker exec gardenlog-app php artisan route:list` shows the new PUT and DELETE routes
- `docker exec gardenlog-app php artisan test --filter=TaskTest` passes (existing tests still green)

#### Manual Verification:

- Hitting `PUT /tasks/{id}` with valid data via browser/curl updates the task
- Hitting `DELETE /tasks/{id}` removes the task
- Attempting to modify another user's task returns 403

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 2: Frontend — Edit Modal, Delete Modal & Task Card Actions

### Overview

Add inline edit/delete icon buttons to each task card. Create edit-task modal (pre-filled fields via Alpine.js) and delete-confirmation modal. Both follow existing `<x-modal>` patterns.

### Changes Required:

#### 1. Task card action buttons

**File**: `resources/views/dashboard.blade.php`

**Intent**: Add pencil (edit) and trash (delete) SVG icon buttons to each task card's right side. Clicking edit dispatches an Alpine event carrying the task's data to open the edit modal. Clicking delete dispatches an event with the task ID to open the delete confirmation modal.

**Contract**: Each task card row (currently lines 39-51) gets a button group after the type badge. The edit button sets Alpine state with the task's id, description, task_date, and type, then opens the `edit-task` modal. The delete button sets the task ID and opens the `confirm-delete-task` modal.

#### 2. Edit task modal

**File**: `resources/views/tasks/partials/edit-task-form.blade.php` (new)

**Intent**: Modal form for editing a task, mirroring the add-task-form structure. Form action uses PUT method to `tasks.update` route. Fields pre-filled from Alpine.js state.

**Contract**: `<x-modal name="edit-task">` containing a form with `@method('PUT')`. The form's `action` attribute is dynamically set to `/tasks/{id}` via Alpine.js. Same fields as add-task-form: description (textarea), task_date (date input), type_choice (select + custom type). All values bound to Alpine data passed when the modal opens.

#### 3. Delete confirmation modal

**File**: `resources/views/tasks/partials/delete-task-form.blade.php` (new)

**Intent**: Confirmation modal before deleting a task. Uses `<x-danger-button>` for the delete action.

**Contract**: `<x-modal name="confirm-delete-task">` containing a form with `@method('DELETE')`. Action dynamically set to `/tasks/{id}`. Shows confirmation text. Cancel (`<x-secondary-button>`) and Delete (`<x-danger-button>`).

#### 4. Include modals in dashboard

**File**: `resources/views/dashboard.blade.php`

**Intent**: Include the two new modal partials at the bottom of the layout, matching the existing `@include('tasks.partials.add-task-form')` pattern.

**Contract**: Add `@include('tasks.partials.edit-task-form')` and `@include('tasks.partials.delete-task-form')` after the existing add-task-form include.

### Success Criteria:

#### Automated Verification:

- `docker exec gardenlog-app php artisan test` passes (no regressions)
- `docker exec gardenlog-app npx vite build` completes without errors

#### Manual Verification:

- Each task card shows pencil and trash icons
- Clicking pencil opens edit modal with correct pre-filled data
- Submitting edit updates the task and shows success flash
- Clicking trash opens confirmation modal
- Confirming delete removes the task and shows success flash
- Cancel buttons close modals without changes
- Type dropdown works correctly (preset types + custom type toggle)

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 3: Tests

### Overview

Add feature tests covering edit/update/delete operations — happy paths, validation, authorization, and data isolation between users.

### Changes Required:

#### 1. Feature tests for update and delete

**File**: `tests/Feature/TaskTest.php`

**Intent**: Extend the existing test file with tests for the update and destroy flows. Cover: successful update, successful delete, validation failures on update, authorization (user cannot update/delete another user's task), and flash messages.

**Contract**: New test methods:
- `test_user_can_update_own_task` — PUT with valid data, assert redirect + DB updated
- `test_user_can_delete_own_task` — DELETE, assert redirect + DB missing
- `test_user_cannot_update_another_users_task` — assert 403
- `test_user_cannot_delete_another_users_task` — assert 403
- `test_update_validates_required_fields` — assert session errors
- `test_update_rejects_future_date` — assert session error on task_date
- `test_guest_cannot_update_task` — assert redirect to login
- `test_guest_cannot_delete_task` — assert redirect to login
- `test_delete_shows_success_flash` — assert session has success message

### Success Criteria:

#### Automated Verification:

- `docker exec gardenlog-app php artisan test --filter=TaskTest` — all tests pass including new ones
- `docker exec gardenlog-app ./vendor/bin/pint --test` — code style passes

#### Manual Verification:

- Review test output to confirm all new test names are descriptive and cover the intended scenarios

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human that the manual testing was successful before proceeding.

---

## Testing Strategy

### Unit Tests:

- TaskPolicy `update` and `delete` methods (ownership check)

### Feature Tests (in `tests/Feature/TaskTest.php`):

- CRUD happy paths: update with all field changes, delete
- Validation: required fields, future date rejection, description max length
- Authorization: cross-user access returns 403
- Guest access: redirects to login
- Flash messages: success messages after update/delete
- Type handling: preset type, custom type, clearing type

### Manual Testing Steps:

1. Log in, create a task, edit it (change description + type), verify changes persist
2. Delete a task, verify it's gone from the list
3. Try editing with empty description — verify validation error shows in modal
4. Log in as a different user — verify you cannot see or modify the first user's tasks

## Performance Considerations

No performance impact — standard single-row UPDATE/DELETE queries on an indexed table. No new queries on the dashboard (tasks are already loaded).

## References

- Roadmap slice: `context/foundation/roadmap.md` (S-03)
- PRD requirements: FR-007 (edit), FR-008 (delete)
- Existing task-log-core plan: `context/changes/task-log-core/plan.md`
- Add task form pattern: `resources/views/tasks/partials/add-task-form.blade.php`
- Modal component: `resources/views/components/modal.blade.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Backend — Policy, Routes, Controller & Request

#### Automated

- [ ] 1.1 `docker exec gardenlog-app php artisan route:list` shows PUT and DELETE task routes
- [ ] 1.2 `docker exec gardenlog-app php artisan test --filter=TaskTest` passes (existing tests green)

#### Manual

- [ ] 1.3 PUT /tasks/{id} with valid data updates the task
- [ ] 1.4 DELETE /tasks/{id} removes the task
- [ ] 1.5 Modifying another user's task returns 403

### Phase 2: Frontend — Edit Modal, Delete Modal & Task Card Actions

#### Automated

- [ ] 2.1 `docker exec gardenlog-app php artisan test` passes (no regressions)
- [ ] 2.2 `docker exec gardenlog-app npx vite build` completes without errors

#### Manual

- [ ] 2.3 Task cards show pencil and trash icon buttons
- [ ] 2.4 Edit modal opens with correct pre-filled data
- [ ] 2.5 Submitting edit updates task and shows success flash
- [ ] 2.6 Delete confirmation modal works and removes task
- [ ] 2.7 Type dropdown works correctly in edit modal

### Phase 3: Tests

#### Automated

- [ ] 3.1 `docker exec gardenlog-app php artisan test --filter=TaskTest` — all new tests pass
- [ ] 3.2 `docker exec gardenlog-app ./vendor/bin/pint --test` — code style passes

#### Manual

- [ ] 3.3 Review test output — all test names are descriptive and cover intended scenarios
