<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: Task Log Core

- **Plan**: context/changes/task-log-core/plan.md
- **Scope**: All Phases (1–4)
- **Date**: 2026-05-26
- **Verdict**: NEEDS ATTENTION
- **Findings**: 0 critical, 3 warnings, 3 observations

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | PASS |
| Scope Discipline | WARNING |
| Safety & Quality | WARNING |
| Architecture | PASS |
| Pattern Consistency | WARNING |
| Success Criteria | PASS |

## Findings

### F1 — Missing test for custom type (__custom__) code path

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Safety & Quality
- **Location**: tests/Feature/TaskTest.php
- **Detail**: StoreTaskRequest::prepareForValidation() has a non-trivial branch: when type_choice is '__custom__', it merges custom_type into the type field. No test exercises this path. If the merge logic broke, no test would catch it.
- **Fix**: Add a dedicated test case exercising the __custom__ flow: post with type_choice='__custom__', custom_type='pruning', assert the task's type column is 'pruning'.
  - Strength: Covers a real code path that's currently untested; prevents silent regressions.
  - Tradeoff: One more test case to maintain.
  - Confidence: HIGH — straightforward test following existing patterns.
  - Blind spot: None significant.
- **Decision**: FIXED — Added custom type test case

### F2 — Task routes outside auth middleware group

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Pattern Consistency
- **Location**: routes/web.php:12-13
- **Detail**: The dashboard and tasks.store routes apply ->middleware('auth') inline, while existing profile routes use Route::middleware('auth')->group(...). Functionally identical but inconsistent.
- **Fix**: Move the two task routes inside the existing auth middleware group block.
- **Decision**: FIXED + ACCEPTED-AS-RULE: Always register new routes inside the existing auth middleware group

### F3 — TaskModelTest in tests/Feature/ instead of tests/Unit/

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Pattern Consistency
- **Location**: tests/Feature/TaskModelTest.php
- **Detail**: The plan specified tests/Unit/TaskModelTest.php. The actual file is at tests/Feature/TaskModelTest.php. These are pure model tests with no HTTP requests — they belong in Unit per Laravel convention and the plan contract.
- **Fix**: Move tests/Feature/TaskModelTest.php to tests/Unit/TaskModelTest.php.
- **Decision**: FIXED — Moved to tests/Unit/ and extended Pest.php to boot Laravel in Unit tests

### F4 — Unplanned DatabaseSeeder changes

- **Severity**: 💡 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Scope Discipline
- **Location**: database/seeders/DatabaseSeeder.php
- **Detail**: DatabaseSeeder was modified to create a test user (password: 'test'). Not in the plan. Low risk as a dev convenience.
- **Fix**: Accept as-is — dev convenience, not production-facing.
- **Decision**: SKIPPED

### F5 — auth() helper vs $request->user() inconsistency

- **Severity**: 💡 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Pattern Consistency
- **Location**: app/Http/Controllers/TaskController.php:13,20
- **Detail**: ProfileController uses $request->user(), while TaskController uses auth()->user(). Functionally identical but inconsistent.
- **Fix**: Change auth()->user() to $request->user() in both methods.
- **Decision**: FIXED — Changed to $request->user() in both methods

### F6 — redirect()->route('dashboard') vs redirect()->back()

- **Severity**: 💡 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Adherence
- **Location**: app/Http/Controllers/TaskController.php:23
- **Detail**: Plan says "redirects back" but implementation uses redirect()->route('dashboard'). Functionally equivalent; explicit route is arguably more predictable.
- **Fix**: Accept as-is — explicit route is fine.
- **Decision**: SKIPPED
