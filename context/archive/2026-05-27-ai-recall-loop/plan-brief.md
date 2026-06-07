# AI Recall Loop — Plan Brief

> Full plan: `context/changes/ai-recall-loop/plan.md`
> Research: `context/changes/ai-recall-loop/research.md`

## What & Why

Gardeners track tasks but retrieving specific history ("when did I last fertilize my tomatoes?") requires manual
scanning. The AI recall loop (S-02) adds a natural-language search bar to the dashboard that answers questions grounded
exclusively in the user's own task data — zero browsing required. This is the core differentiating feature of
GardenLog (FR-009, FR-010).

## Starting Point

S-01 (task-log-core) is complete: `Task` model with user scoping, `TaskController` with CRUD, auth middleware, dashboard
with Alpine.js modals, Pest test suite. No AI integration exists — no LLM library, no service layer, no OpenRouter
config. The composite index on `[user_id, task_date]` is already in place for efficient task retrieval.

## Desired End State

A logged-in gardener sees a search bar above their task list on the dashboard. They type a question, see a loading
spinner, and within 5 seconds receive an inline answer referencing actual dates from their task history. If no matching
task exists, the AI says so explicitly. The feature is fully tested with `Prism::fake()` — no real API calls in CI.

## Key Decisions Made

| Decision                 | Choice                                     | Why (1 sentence)                                                                                | Source   |
|--------------------------|--------------------------------------------|-------------------------------------------------------------------------------------------------|----------|
| LLM client library       | Prism PHP                                  | Native OpenRouter provider, Blade system prompts, Prism::fake() for testing, Laravel 13 support | Research |
| AI provider              | OpenRouter (Claude Sonnet 4.5)             | Unified API gateway, model switching via env var, matches tech-stack decision                   | Research |
| UI placement             | Inline on dashboard above task list        | Zero navigation — matches PRD's "conversational exchange in the search bar"                     | Plan     |
| Request type             | AJAX fetch with Alpine.js                  | Smooth UX without page reload; loading state and error handling in-place                        | Plan     |
| Prompt injection defense | Input sanitization + strict system prompt  | Pragmatic for MVP — attack surface limited to user's own data                                   | Plan     |
| Context injection limit  | Latest 50 tasks                            | Predictable token usage (~3-5k tokens); covers practical recall window                          | Plan     |
| Error UX                 | Friendly message + retry button            | Honest UX — user knows it failed and can act; handles transient errors                          | Plan     |
| Prompt template          | Blade view with grounding rules            | Version-controlled, testable, uses Blade loops for task rendering                               | Plan     |
| Query persistence        | No storage for MVP                         | Simplest implementation; respects privacy; no conversation logs                                 | Plan     |
| Test strategy            | Feature tests (Prism::fake()) + unit tests | Covers critical paths without real API calls; matches existing Pest patterns                    | Plan     |

## Scope

**In scope:**

- Prism PHP installation and OpenRouter configuration
- `AiRecallService` — first service class in the project
- Blade system prompt template with strict grounding rules
- `POST /tasks/ask` JSON endpoint with validation
- Inline Alpine.js search bar on dashboard with loading/error states
- Feature + unit tests with `Prism::fake()`
- Input sanitization (trim, strip HTML, length limits)

**Out of scope:**

- Conversation history / multi-turn context
- Query logging / analytics
- Streaming responses
- RAG / vector embeddings
- Dedicated /ask page
- Livewire

## Architecture / Approach

Context injection pattern: `AiRecallService` fetches the user's latest 50 tasks via Eloquent, renders them into a Blade
system prompt template (`prompts/garden-recall.blade.php`), calls Prism PHP with `Provider::OpenRouter`, and returns a
typed `AiRecallResult` value object. The controller returns JSON, and the Alpine.js frontend renders the response
inline. No new database tables — the only infrastructure change is adding Prism PHP and OpenRouter env vars.

## Phases at a Glance

| Phase                             | What it delivers                                            | Key risk                                                         |
|-----------------------------------|-------------------------------------------------------------|------------------------------------------------------------------|
| 1. Backend Foundation             | Prism PHP installed, AiRecallService, Blade prompt template | Prism config/publish may have unexpected defaults                |
| 2. Route, Controller & Validation | POST /tasks/ask endpoint with JSON responses                | Route registration must follow lessons.md rule (auth group)      |
| 3. Frontend UI                    | Inline search bar with fetch, loading, error + retry        | Alpine.js state management for async flow                        |
| 4. Testing & Hardening            | Full test suite with Prism::fake(), input sanitization      | Prompt grounding quality — may need iteration after real testing |

**Prerequisites:** OpenRouter API key (sign up at openrouter.ai), S-01 task-log-core complete (PR #16 merged)
**Estimated effort:** ~2-3 sessions across 4 phases

## Open Risks & Assumptions

- Prompt grounding quality is iterative — the initial system prompt may need tuning after real-world testing to prevent
  hallucinated dates
- 50-task context limit assumes a typical home gardener; power users may need a higher limit later
- OpenRouter API availability and latency directly affect the 5-second NFR

## Success Criteria (Summary)

- Gardener asks "when did I last fertilize my tomatoes?" and gets the correct date from their task history
- AI never invents dates or events — says "I don't see that" when no match exists
- Response visible within 5 seconds; errors show a retry button
