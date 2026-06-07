# AI Search UX Polish — Plan Brief

> Full plan: `context/changes/ai-search-ux/plan.md`

## What & Why

Polish the AI search component on the dashboard with UX affordances discovered during S-02 manual testing. The disabled
Ask button offers no feedback when the query is too short, the AI answer lacks context about what was asked, and users
with no tasks get no guidance before wasting an API call.

## Starting Point

The AI search partial (`ai-search.blade.php`) is fully functional: Alpine.js-powered form, loading state, error/retry,
and answer display. But the disabled-button state is unexplained, the answer box has no question context, and zero-tasks
users get no hint. Query preservation after submission already works.

## Desired End State

Helper text below the input explains the 5-character minimum and disappears once met. The green answer box shows "You
asked: [question]" above the AI response. Users with zero tasks see a contextual hint instead of the search form. The
search experience feels polished and self-explanatory.

## Key Decisions Made

| Decision       | Choice                         | Why (1 sentence)                                                         |
|----------------|--------------------------------|--------------------------------------------------------------------------|
| Hint style     | Always-visible helper text     | Zero-effort discoverability — user sees it immediately without guessing. |
| Answer display | Echo question above answer     | Gives context to the response, especially after typing a new query.      |
| Empty-tasks UX | Contextual hint replacing form | Prevents wasted API call and confusion when AI returns "no data."        |
| Post-submit    | Keep query in input            | Already works — no change needed; allows easy query refinement.          |

## Scope

**In scope:** Helper text hint, question echo, empty-tasks hint, tests for new view state.

**Out of scope:** Backend changes to AI service, character counter, input clearing, layout redesign.

## Architecture / Approach

Primarily a single Blade partial change (`ai-search.blade.php`) with Alpine.js conditional rendering. One minor
controller touch: pass `$hasNoTasks` boolean from `TaskController::index` using the already-loaded paginated collection.
No new routes, models, or services.

## Phases at a Glance

| Phase        | What it delivers                        | Key risk                    |
|--------------|-----------------------------------------|-----------------------------|
| 1. UX Polish | All three affordances + controller flag | Minimal — Blade/Alpine only |
| 2. Tests     | Feature tests for new view state        | None                        |

**Prerequisites:** S-02 (ai-recall-loop) is implemented.
**Estimated effort:** ~1 session across 2 phases.

## Open Risks & Assumptions

- Assumes `$tasks->isEmpty()` on the paginated first page correctly identifies zero-tasks users
- Assumes `docker exec` workflow is active (per lessons.md)

## Success Criteria (Summary)

- Disabled Ask button is always accompanied by a visible explanation
- AI answer always shows what question produced it
- Zero-tasks users see guidance before attempting to search
