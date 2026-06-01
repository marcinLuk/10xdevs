# AI Search UX Polish Implementation Plan

## Overview

Polish the AI search component on the dashboard with four UX affordances discovered during S-02 manual testing: (1)
helper text hint for the disabled Ask button, (2) question echo above the AI answer, (3) contextual hint when no tasks
exist, and (4) preserved query text after submission. Primarily a frontend change to one Blade partial, with a minor
controller adjustment for the empty-tasks flag.

## Current State Analysis

The AI search partial at `resources/views/tasks/partials/ai-search.blade.php` is functional but offers no feedback when
the Ask button is disabled (query < 5 characters). The answer display shows only the AI response with no context about
what was asked. Users with no logged tasks can still submit a question and waste an API call only to get a "no data"
response. The query text is already preserved after submission (Alpine.js state persists), so affordance (4) is already
working — no change needed.

### Key Discoveries:

- `ai-search.blade.php:61` — button is disabled via `::disabled="loading || query.trim().length < 5"` but no hint text
  exists
- `ai-search.blade.php:73-75` — answer display shows only `x-text="answer"` with no question context
- `TaskController::index` at `app/Http/Controllers/TaskController.php:12-16` passes `$tasks` (paginated) to the
  dashboard — the empty check can use `$tasks->isEmpty()` which is already available
- Alpine.js `query` state already persists after submission — affordance (4) needs no code change

## Desired End State

When the Ask button is disabled, small grey helper text below the input reads "Type at least 5 characters to ask" and
disappears once the threshold is met. After the AI responds, the green answer box shows "You asked: [question]" above
the answer text. When the user has zero tasks, the search section shows a contextual hint: "Log some tasks first — the
AI searches your task history." Query text remains in the input after a successful answer (already works).

## What We're NOT Doing

- Backend logic changes to AiRecallService or AiRecallController
- Character counter or tooltip variants — simple helper text was chosen
- Clearing the input after submission — keeping current behavior
- Redesigning the search layout or styling beyond the four affordances
- Adding loading skeletons or animation changes

## Implementation Approach

Modify the single Blade partial `ai-search.blade.php` for three of the four affordances (hint text, question echo,
empty-tasks hint). The empty-tasks hint requires passing an `$hasNoTasks` boolean from the controller — this is the only
backend touch. The query preservation already works, so it's a no-op.

---

## Phase 1: AI Search UX Polish

### Overview

Add all three UX affordances to the ai-search partial and pass the empty-tasks flag from the controller.

### Changes Required:

#### 1. Pass empty-tasks flag from controller

**File**: `app/Http/Controllers/TaskController.php`

**Intent**: Pass a boolean to the dashboard view indicating whether the user has any tasks at all, so the ai-search
partial can show a contextual hint.

**Contract**: Add `$hasNoTasks = $tasks->isEmpty()` and pass it to the view via `compact('tasks', 'hasNoTasks')`. Note:
`$tasks->isEmpty()` checks the current page, but for a user with truly zero tasks the first page will be empty. For
users with tasks spanning multiple pages, this will correctly be `false`.

#### 2. Helper text hint for disabled Ask button

**File**: `resources/views/tasks/partials/ai-search.blade.php`

**Intent**: Show "Type at least 5 characters to ask" below the input when the query is too short, so the disabled button
state is never mysterious.

**Contract**: Add a `<p>` element after the `<form>` that is visible via `x-show` when
`query.trim().length < 5 && !loading`. Small grey text (`text-xs text-gray-400`). Hidden once the threshold is met or
while loading.

#### 3. Question echo above answer

**File**: `resources/views/tasks/partials/ai-search.blade.php`

**Intent**: Display "You asked: [question]" above the AI answer in the green success box, giving context to the
response.

**Contract**: Inside the answer `div` (currently line 73), add a `<p>` element above the answer text that shows
`lastQuery` using `x-text`. Styled smaller and slightly muted (`text-xs text-green-700 mb-1 font-medium`). Only visible
when `answer` is truthy (same condition as parent div).

#### 4. Empty-tasks contextual hint

**File**: `resources/views/tasks/partials/ai-search.blade.php`

**Intent**: When the user has no tasks, show a hint explaining that the AI searches task history, preventing a wasted
API call.

**Contract**: Add a Blade `@if($hasNoTasks)` block that renders a hint message ("Log some tasks first — the AI searches
your task history") and visually disables or hides the search form. The hint replaces the form when `$hasNoTasks` is
true, using the same card container for consistent styling.

### Success Criteria:

#### Automated Verification:

- `docker exec gardenlog-app php artisan test` — all existing tests pass (no regressions)
- `docker exec gardenlog-app npx vite build` — frontend builds without errors

#### Manual Verification:

- With empty query, helper text "Type at least 5 characters to ask" is visible below input
- After typing 5+ characters, helper text disappears
- After getting an AI answer, "You asked: [question]" appears above the answer
- With zero tasks, the search section shows the contextual hint instead of the search form
- Retry button still works and shows question echo correctly
- Error state still displays correctly

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 2: Tests

### Overview

Update existing feature tests to verify the new UX elements render correctly.

### Changes Required:

#### 1. Test helper text and question echo rendering

**File**: `tests/Feature/AiRecallTest.php`

**Intent**: Add tests verifying that the dashboard renders the helper text hint and that the view receives the
`$hasNoTasks` variable. Test that a user with no tasks sees the contextual hint.

**Contract**: New test methods:

- `test_dashboard_passes_has_no_tasks_flag_when_empty` — authenticate a user with no tasks, GET /dashboard, assert view
  has `hasNoTasks` as `true`
- `test_dashboard_passes_has_no_tasks_flag_when_tasks_exist` — authenticate a user with tasks, GET /dashboard, assert
  view has `hasNoTasks` as `false`
- `test_empty_tasks_hint_is_visible_when_no_tasks` — assert the response body contains the hint text
- `test_search_form_is_visible_when_tasks_exist` — assert the response body contains the search input

### Success Criteria:

#### Automated Verification:

- `docker exec gardenlog-app php artisan test --filter=AiRecallTest` — all tests pass including new ones
- `docker exec gardenlog-app ./vendor/bin/pint --test` — code style passes

#### Manual Verification:

- Review test output to confirm new test names are descriptive and cover intended scenarios

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation.

---

## Testing Strategy

### Feature Tests (in `tests/Feature/AiRecallTest.php`):

- `$hasNoTasks` view variable passed correctly in both empty and non-empty states
- Empty-tasks hint text renders when no tasks exist
- Search form renders when tasks exist

### Manual Testing Steps:

1. Register a new user (zero tasks) — verify contextual hint appears instead of search form
2. Add a task — verify search form appears and hint disappears
3. Type 3 characters — verify helper text visible, Ask button disabled
4. Type 5+ characters — verify helper text gone, Ask button enabled
5. Submit a question — verify "You asked: [question]" appears above the answer
6. Click Retry on an error — verify question echo shows correctly

## Performance Considerations

No performance impact. The `$hasNoTasks` flag uses `$tasks->isEmpty()` on the already-loaded paginated collection — no
additional query.

## References

- Roadmap slice: `context/foundation/roadmap.md` (S-04)
- PRD: FR-009 (natural-language AI search)
- Current partial: `resources/views/tasks/partials/ai-search.blade.php`
- AI recall tests: `tests/Feature/AiRecallTest.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See
`references/progress-format.md`.

### Phase 1: AI Search UX Polish

#### Automated

- [x] 1.1 `docker exec gardenlog-app php artisan test` — all existing tests pass — 82dbf7e
- [x] 1.2 `docker exec gardenlog-app npx vite build` — frontend builds without errors — 82dbf7e

#### Manual

- [x] 1.3 Helper text visible with empty/short query, disappears at 5+ chars — 82dbf7e
- [x] 1.4 Question echo shows "You asked: [question]" above AI answer — 82dbf7e
- [x] 1.5 Empty-tasks hint appears instead of search form for users with no tasks — 82dbf7e
- [x] 1.6 Retry and error states still work correctly — 82dbf7e

### Phase 2: Tests

#### Automated

- [x] 2.1 `docker exec gardenlog-app php artisan test --filter=AiRecallTest` — all tests pass
- [x] 2.2 `docker exec gardenlog-app ./vendor/bin/pint --test` — code style passes

#### Manual

- [x] 2.3 Review test output — new test names are descriptive
