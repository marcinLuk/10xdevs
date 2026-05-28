# AI Recall Loop Implementation Plan

## Overview

Implement the AI-powered recall feature (S-02) that lets gardeners ask natural-language questions about their task
history and receive grounded, date-specific answers. Uses Prism PHP with OpenRouter (Claude Sonnet 4.5) for LLM calls,
context injection via Blade-templated system prompts, and an inline dashboard search bar powered by Alpine.js fetch.

## Current State Analysis

The task management core (S-01) is complete: `Task` model with `scopeForUser()`, `TaskController` with `index`/`store`,
auth middleware group in `routes/web.php`, and a dashboard view with Alpine.js modals. No AI integration exists yet — no
Prism PHP, no OpenRouter config, no service layer.

### Key Discoveries:

- `app/Http/Controllers/TaskController.php:12-17` — user scoping via `$request->user()->tasks()` relationship
- `app/Models/Task.php:25-28` — `scopeForUser()` scope and composite index on `[user_id, task_date]`
- `routes/web.php:12-20` — auth middleware group where new route must be registered (per `lessons.md` rule)
- `resources/views/dashboard.blade.php:20-28` — header area where search bar will be inserted
- `app/Http/Requests/StoreTaskRequest.php:15-26` — `prepareForValidation()` pattern to follow
- `resources/js/app.js:1-7` — Alpine.js setup, minimal configuration
- No `config/prism.php` exists — clean install path
- No existing service classes — `AiRecallService` will be the first

## Desired End State

A gardener on the dashboard sees a search bar above their task list. They type a question like "when did I last
fertilize my tomatoes?", press Enter or click Ask, see a loading spinner, and within 5 seconds receive an inline answer
referencing actual dates from their task history. If no matching task exists, the AI explicitly says so. If the API call
fails, a friendly error message with a retry button appears. The feature is tested with `Prism::fake()` and covers auth
guards, happy path, empty history, and error scenarios.

## What We're NOT Doing

- No conversation history or multi-turn context — each query is independent, fire-and-forget
- No query logging or analytics table — no `ai_queries` migration
- No streaming responses — synchronous `asText()` for MVP
- No RAG or vector embeddings — context injection (full task dump) is sufficient
- No Livewire — Alpine.js fetch handles the async UX
- No dedicated /ask page — everything is inline on the dashboard

## Implementation Approach

Context injection architecture: fetch the user's latest 50 tasks via Eloquent, render them into a Blade system prompt
template with strict grounding rules, send to OpenRouter via Prism PHP, return the text response as JSON. The frontend
uses Alpine.js to submit via fetch and render the response inline without page reload.

---

## Phase 1: Backend Foundation

### Overview

Install Prism PHP, configure OpenRouter credentials, create the `AiRecallService` service class, and build the Blade
system prompt template. This phase establishes the AI backend without any routes or UI.

### Changes Required:

#### 1. Install Prism PHP

**File**: `composer.json` (modified by Composer)

**Intent**: Add Prism PHP as a project dependency. This is the LLM client library that provides native OpenRouter
support and `Prism::fake()` for testing.

**Contract**: Run `composer require prism-php/prism` — this adds the package and publishes `config/prism.php` via
`php artisan vendor:publish --tag=prism-config`.

#### 2. Environment Configuration

**File**: `.env` and `.env.example`

**Intent**: Add OpenRouter API key and default model configuration so the AI provider is configurable without code
changes.

**Contract**: Add three env vars: `OPENROUTER_API_KEY`, `OPENROUTER_MODEL` (default: `anthropic/claude-sonnet-4-5`),
`AI_RECALL_TASK_LIMIT` (default: `50`). Update both `.env` and `.env.example`.

#### 3. Prism Config Customization

**File**: `config/prism.php`

**Intent**: Configure Prism to use OpenRouter as the default provider with credentials from env vars.

**Contract**: After publishing Prism's config, verify that `prism.providers.openrouter` section exists and references
the `OPENROUTER_API_KEY` env var. Add a custom `ai_recall` section for the model name and task limit if not handled by
Prism's default config structure.

#### 4. AiRecallService

**File**: `app/Services/AiRecallService.php` (new)

**Intent**: Encapsulate all Prism interaction in a single service class. Keeps the controller thin and isolates the LLM
dependency. This is the first service class in the project, establishing the service layer pattern.

**Contract**: Public method `ask(User $user, string $question): AiRecallResult`. Internally: fetches user's latest N
tasks (configurable via `AI_RECALL_TASK_LIMIT`), renders the Blade system prompt with task data, calls `Prism::text()`
with `Provider::OpenRouter`, returns a value object containing the answer text (or error state). Handles
`PrismException` and returns error result rather than throwing.

#### 5. AiRecallResult Value Object

**File**: `app/Services/AiRecallResult.php` (new)

**Intent**: Typed return value from `AiRecallService::ask()` — avoids returning raw arrays or mixed types.

**Contract**: Two named constructors: `AiRecallResult::success(string $answer)` and
`AiRecallResult::error(string $message)`. Properties: `bool $ok`, `?string $answer`, `?string $error`.

#### 6. Blade System Prompt Template

**File**: `resources/views/prompts/garden-recall.blade.php` (new)

**Intent**: Version-controlled, testable prompt template that defines the AI's role, strict grounding rules, and formats
the user's task data for context injection. Using Blade allows full template syntax (loops, conditionals) for rendering
task lists.

**Contract**: Receives `$tasks` (Collection of Task models). Renders: role definition ("You are a garden task recall
assistant"), grounding rules ("ONLY answer from the task data below; NEVER invent dates or events; if no matching task
exists, say so explicitly"), task data as a structured list (date, description, type for each task), and response format
instructions (concise, date-specific answers).

### Success Criteria:

#### Automated Verification:

- `composer install` succeeds with Prism PHP installed
- `config/prism.php` exists after publish
- `php artisan tinker --execute="app(App\Services\AiRecallService::class)"` resolves without error
- `./vendor/bin/pint` passes on new files
- `composer test` passes (no regressions)

#### Manual Verification:

- `.env.example` contains the three new env vars with sensible defaults/placeholders
- Blade prompt template renders correctly with sample task data (visually inspect via tinker or a quick route)

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 2: Route, Controller & Validation

### Overview

Create the HTTP layer: a JSON endpoint that accepts the user's question, validates it, calls `AiRecallService`, and
returns the AI response. Follows existing patterns from `TaskController` and `StoreTaskRequest`.

### Changes Required:

#### 1. AiRecallRequest Form Request

**File**: `app/Http/Requests/AiRecallRequest.php` (new, via `php artisan make:request`)

**Intent**: Validate the AI query input using the established form request pattern. Enforces required string, min 5
chars, max 500 chars.

**Contract**: Rules: `question` — `required|string|min:5|max:500`. Authorization returns `true` (relies on route
middleware). Input sanitization in `prepareForValidation()`: trim whitespace, strip HTML tags.

#### 2. AiRecallController

**File**: `app/Http/Controllers/AiRecallController.php` (new, via `php artisan make:controller`)

**Intent**: Thin controller that delegates to `AiRecallService` and returns a JSON response. Follows the
single-responsibility pattern — controller handles HTTP, service handles AI logic.

**Contract**: Single method `ask(AiRecallRequest $request): JsonResponse`. Injects `AiRecallService`, calls
`ask($request->user(), $request->validated()['question'])`, returns JSON
`{ ok: bool, answer: ?string, error: ?string }`. HTTP status: 200 for success, 200 with `ok: false` for AI errors (not
500 — the server handled it correctly, the AI just couldn't answer).

#### 3. Route Registration

**File**: `routes/web.php`

**Intent**: Add the AI recall endpoint inside the existing auth middleware group, per the `lessons.md` rule.

**Contract**: `POST /tasks/ask` → `AiRecallController@ask`, named `tasks.ask`. Must be inside the
`Route::middleware('auth')->group()` block (lines 12-20).

### Success Criteria:

#### Automated Verification:

- `php artisan route:list` shows `POST tasks/ask` with auth middleware
- `./vendor/bin/pint` passes
- `composer test` passes (no regressions)

#### Manual Verification:

- `POST /tasks/ask` with valid auth + `{"question": "when did I water?"}` returns JSON response
- Unauthenticated request to `/tasks/ask` redirects to login
- Invalid input (empty, too short, too long) returns 422 with validation errors

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 3: Frontend UI

### Overview

Add the inline AI search bar to the dashboard with Alpine.js fetch, loading spinner, response display, and error
handling with retry button. No page reloads — everything happens via AJAX.

### Changes Required:

#### 1. AI Search Bar Partial

**File**: `resources/views/tasks/partials/ai-search.blade.php` (new)

**Intent**: Reusable Blade partial containing the Alpine.js-powered search bar component. Handles form submission via
fetch, loading state, response rendering, and error display with retry.

**Contract**: Alpine `x-data` component with state: `query`, `answer`, `error`, `loading`. On submit:
`fetch('/tasks/ask', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': ..., 'Accept': 'application/json' }, body: JSON.stringify({ question: query }) })`.
Shows spinner during loading, renders answer text on success, shows error message + retry button on failure. CSRF token
from `<meta name="csrf-token">`. Disables submit button during loading to prevent double-sends.

#### 2. Dashboard Integration

**File**: `resources/views/dashboard.blade.php`

**Intent**: Include the AI search bar partial on the dashboard, positioned above the task list between the header and
task entries.

**Contract**: Add `@include('tasks.partials.ai-search')` after the header `<div>` (around line 28) and before the
empty-state check (line 30). The search bar should be visually distinct but cohesive with the existing card design.

#### 3. Styling

**File**: `resources/css/app.css` (potentially)

**Intent**: Ensure the search bar, response area, loading spinner, and error state are styled consistently with the
existing Tailwind-based design.

**Contract**: Use Tailwind utility classes directly in the Blade template. The search bar should use the same card
styling as the task list container. Loading spinner can use Tailwind's `animate-spin`. No custom CSS needed unless
existing utilities are insufficient.

### Success Criteria:

#### Automated Verification:

- `npm run build` succeeds
- `./vendor/bin/pint` passes
- `composer test` passes (no regressions)

#### Manual Verification:

- Search bar visible on dashboard above task list
- Typing a question and submitting shows loading spinner
- Successful AI response displays inline below the search bar
- API error shows friendly error message with retry button
- Retry button re-submits the same question
- Empty query submission is prevented (HTML validation + Alpine guard)
- Search bar is visually consistent with dashboard design
- CSRF token is included in requests (check browser dev tools)

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Phase 4: Testing & Hardening

### Overview

Write comprehensive tests using `Prism::fake()` for the AI integration, unit tests for the service class, and feature
tests for the full HTTP flow. Cover auth guards, happy path, empty history, validation errors, and API failure
scenarios.

### Changes Required:

#### 1. Feature Tests

**File**: `tests/Feature/AiRecallTest.php` (new, via `php artisan make:test`)

**Intent**: Test the full HTTP request cycle for the AI recall endpoint, following existing patterns from
`tests/Feature/TaskTest.php`.

**Contract**: Test cases using `Prism::fake()`:

- Guest cannot access `/tasks/ask` (redirect to login)
- Authenticated user with tasks gets a successful AI response
- Authenticated user with no tasks gets "I don't see any tasks" type response
- Invalid input (too short, too long, missing) returns 422
- Prism API failure returns `{ ok: false, error: "..." }`
- User can only query their own tasks (data isolation)

#### 2. Unit Tests for AiRecallService

**File**: `tests/Unit/AiRecallServiceTest.php` (new, via `php artisan make:test --unit`)

**Intent**: Test the service class in isolation — prompt building logic, task fetching, result handling.

**Contract**: Test cases:

- Service fetches only the authenticated user's tasks
- Task limit is respected (only latest N tasks)
- Tasks are ordered by `task_date` descending
- `AiRecallResult::success()` and `AiRecallResult::error()` produce correct state

#### 3. Update .env.example Documentation

**File**: `.env.example`

**Intent**: Ensure env vars have clear comments explaining their purpose, so new developers know what to configure.

**Contract**: Add inline comments above each new env var explaining what it does and where to get the API key.

### Success Criteria:

#### Automated Verification:

- `composer test` passes with all new tests green
- `./vendor/bin/pint` passes
- All existing tests still pass (no regressions)
- Test coverage includes: auth guard, happy path, empty history, validation, API error, data isolation

#### Manual Verification:

- End-to-end flow works: log in, have tasks, ask a question, get a correct grounded answer
- Ask about a task that doesn't exist, AI says "I don't see that"
- AI never invents dates or events not in the task history
- Response arrives within 5 seconds (NFR)

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual
confirmation from the human that the manual testing was successful before proceeding to the next phase.

---

## Testing Strategy

### Unit Tests:

- `AiRecallService` — task fetching, limit enforcement, ordering, result value objects
- `AiRecallResult` — success/error factory methods, property access

### Feature Tests (with Prism::fake()):

- Auth guard on `POST /tasks/ask`
- Happy path: user with tasks, valid question, AI returns answer
- Empty task history: AI responds with "no tasks found" equivalent
- Validation: short/long/empty/missing input → 422
- API failure: Prism throws → graceful error response
- Data isolation: user A's query doesn't include user B's tasks

### Manual Testing Steps:

1. Log in, create several tasks with different dates and types
2. Ask "when did I last water?" — verify answer references actual dates
3. Ask about a non-existent task — verify AI says it doesn't see it
4. Disconnect internet or use invalid API key — verify error + retry UX
5. Submit empty/very short query — verify validation feedback
6. Check browser network tab — verify CSRF token, JSON payload, response format

## Performance Considerations

- **Task context limit**: Capped at 50 tasks to keep token usage predictable (~3-5k prompt tokens)
- **5-second NFR**: Prism's `withClientOptions(['timeout' => 10])` with `withClientRetry(2, 100)` provides a safety
  margin
- **No caching**: Each query hits the LLM fresh — acceptable for MVP given low expected QPS
- **Composite index**: Existing `[user_id, task_date]` index on tasks table ensures fast context queries

## Migration Notes

No database migrations needed — this feature adds no new tables or columns. The only data changes are:

- New env vars in `.env` (API key, model, task limit)
- New Composer dependency (Prism PHP)
- New npm build (if CSS changes, though likely just Tailwind classes)

## References

- Research: `context/changes/ai-recall-loop/research.md`
- Prism PHP docs: `context/changes/ai-recall-loop/prism-php-docs.md`
- PRD requirements: `context/foundation/prd.md:83-87,98-102` (FR-009, FR-010, NFRs)
- Tech stack: `context/foundation/tech-stack.md:19,25` (OpenRouter + grounding architecture)
- Existing task controller: `app/Http/Controllers/TaskController.php:12-17`
- Existing task model: `app/Models/Task.php:25-28`
- Route group: `routes/web.php:12-20`
- Dashboard insertion point: `resources/views/dashboard.blade.php:20-28`
- Lessons: `context/foundation/lessons.md` (route registration rule)

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See
`references/progress-format.md`.

### Phase 1: Backend Foundation

#### Automated

- [x] 1.1 composer install succeeds with Prism PHP
- [x] 1.2 config/prism.php exists after publish
- [x] 1.3 AiRecallService resolves from container
- [x] 1.4 Pint passes on new files
- [x] 1.5 composer test passes (no regressions)

#### Manual

- [x] 1.6 .env.example contains new env vars with defaults
- [x] 1.7 Blade prompt template renders correctly with sample data

### Phase 2: Route, Controller & Validation

#### Automated

- [ ] 2.1 route:list shows POST tasks/ask with auth middleware
- [ ] 2.2 Pint passes
- [ ] 2.3 composer test passes (no regressions)

#### Manual

- [ ] 2.4 POST /tasks/ask with valid auth returns JSON response
- [ ] 2.5 Unauthenticated request redirects to login
- [ ] 2.6 Invalid input returns 422 with validation errors

### Phase 3: Frontend UI

#### Automated

- [ ] 3.1 npm run build succeeds
- [ ] 3.2 Pint passes
- [ ] 3.3 composer test passes (no regressions)

#### Manual

- [ ] 3.4 Search bar visible on dashboard above task list
- [ ] 3.5 Loading spinner shows during AI query
- [ ] 3.6 Successful response displays inline
- [ ] 3.7 Error shows message with retry button
- [ ] 3.8 Visual consistency with dashboard design
- [ ] 3.9 CSRF token included in requests
- [ ] 3.10 Retry button re-submits the same question
- [ ] 3.11 Empty query submission prevented

### Phase 4: Testing & Hardening

#### Automated

- [ ] 4.1 All new tests pass (composer test)
- [ ] 4.2 Pint passes
- [ ] 4.3 No regressions in existing tests
- [ ] 4.4 Coverage includes: auth, happy path, empty history, validation, API error, data isolation

#### Manual

- [ ] 4.5 End-to-end: ask question, get correct grounded answer
- [ ] 4.6 Non-existent task: AI says "I don't see that"
- [ ] 4.7 AI never invents dates or events
- [ ] 4.8 Response within 5 seconds
