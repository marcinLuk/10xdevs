---
date: 2026-05-27T12:00:00+02:00
researcher: Claude (10x-research)
git_commit: 3e92b74
branch: feature/ai-recall-loop
repository: marcinLuk/10xdevs
topic: "AI recall loop — library selection & Prism PHP compatibility analysis"
tags: [ research, codebase, ai-recall-loop, prism-php, openrouter, laravel ]
status: complete
last_updated: 2026-05-27
last_updated_by: Claude (10x-research)
---

# Research: AI recall loop — library selection & Prism PHP compatibility

**Date**: 2026-05-27
**Researcher**: Claude (10x-research)
**Git Commit**: 3e92b74
**Branch**: feature/ai-recall-loop
**Repository**: marcinLuk/10xdevs

## Research Question

Is Prism PHP (`prism-php/prism`) compatible with our project for implementing S-02 (ai-recall-loop)? Verify against
codebase versions, existing patterns, tech-stack decisions, and PRD requirements.

## Summary

**Prism PHP is fully compatible.** The project runs PHP 8.5 + Laravel 13.11 (Prism requires PHP 8.2+, Laravel 11-13).
No existing AI packages conflict. Prism's native OpenRouter provider and Blade-based system prompts align perfectly with
the tech-stack decision (`ai_provider: openrouter`) and the context-injection architecture needed for S-02. The
`Prism::fake()` testing API pairs well with the existing Pest test suite.

## Detailed Findings

### 1. Version Compatibility

| Requirement     | Project value                    | Prism requirement | Status |
|-----------------|----------------------------------|-------------------|--------|
| PHP             | 8.5.0 (composer.json: `^8.4`)    | 8.2+              | ✅      |
| Laravel         | 13.11.2 (composer.json: `^13.8`) | 11-13             | ✅      |
| Existing AI pkg | None                             | —                 | ✅      |
| AI env vars     | None in `.env` / `.env.example`  | —                 | ✅      |

- `composer.json:9` — `"php": "^8.4"`
- `composer.json:10` — `"laravel/framework": "^13.8"`
- No `config/prism.php` exists yet — clean install path.

### 2. Tech-Stack Alignment

Source: `context/foundation/tech-stack.md`

- **AI provider**: OpenRouter (unified API gateway, OpenAI-compatible endpoint)
- **Key quote** (line 25): "the data grounding logic (matching queries to Eloquent-persisted task history) stays
  entirely in Laravel"
- **Model switching**: "a configuration change rather than a code change"

Prism PHP provides a **native OpenRouter provider** (`Provider::OpenRouter`) since PR #470 (July 2025). This maps
directly to our tech-stack decision — no custom HTTP client or adapter needed.

### 3. PRD Requirements vs Prism Capabilities

| PRD Requirement                                           | Source     | Prism PHP Coverage                                                            |
|-----------------------------------------------------------|------------|-------------------------------------------------------------------------------|
| FR-009: Natural-language question input                   | prd.md:85  | Prism handles the LLM call; input UI is our Blade/Alpine code                 |
| FR-010: Answer drawn exclusively from user's task history | prd.md:86  | Context injection via `withSystemPrompt()` — grounding logic is app-side      |
| NFR: No invented dates/events                             | prd.md:99  | System prompt engineering + context injection; Prism is the transport         |
| NFR: Response within 5 seconds                            | prd.md:98  | Prism adds minimal HTTP overhead; 5s budget is generous for context injection |
| NFR: Task data used only for user's own queries           | prd.md:102 | User-scoped Eloquent query → injected as context; Prism doesn't store data    |

**Grounding architecture**: Prism is a client library, not a RAG engine. Grounding is our responsibility —
fetch user's tasks via Eloquent, inject as system prompt context, let the LLM answer based only on that context.
This matches the tech-stack's explicit design: "context injection — passing task history as context to the AI provider —
is a well-understood integration pattern" (roadmap.md, S-02 Unknowns).

### 4. Prism API Features Relevant to S-02

From `context/changes/ai-recall-loop/prism-php-docs.md` (Context7, v0.100.1):

**a) Text generation with system prompt** — core S-02 pattern:

```php
$response = Prism::text()
    ->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')
    ->withSystemPrompt(view('prompts.garden-recall', ['tasks' => $userTasks]))
    ->withPrompt($userQuestion)
    ->asText();
```

Key advantage: `withSystemPrompt(view(...))` accepts a Blade view directly — we can template the grounding
prompt with full Blade power (loops, conditionals, formatting) without string concatenation.

**b) Error handling + retry**:

```php
->withClientOptions(['timeout' => 30])
->withClientRetry(3, 100)
```

Meets the 5-second NFR with configurable timeout and automatic retry.

**c) Testing with `Prism::fake()`**:

```php
Prism::fake([TextResponseFake::make()->withText('You last fertilized tomatoes on May 15.')]);
```

Integrates with our Pest test suite (`pestphp/pest` 4.7). Assertions:
`$fake->assertPrompt(...)`, `$fake->assertCallCount(...)`, `$fake->assertRequest(...)`.

**d) Token usage tracking**:

```php
$response->usage->promptTokens;
$response->usage->completionTokens;
```

Useful for cost monitoring per query.

**e) Streaming** (optional enhancement):

```php
$stream = Prism::text()->...->asStream();
```

Not required for MVP but available if we want sub-5s perceived response time.

### 5. Codebase Integration Points

| Component     | Existing Pattern                                                  | AI Integration                                         |
|---------------|-------------------------------------------------------------------|--------------------------------------------------------|
| Controller    | `TaskController` — `$request->user()->tasks()` scoping            | New `AiRecallController` or method on `TaskController` |
| Model         | `Task` — `scopeForUser()`, composite index `[user_id, task_date]` | Leverage scope + index for context query               |
| Routes        | `routes/web.php` — auth middleware group (line 12-20)             | Add `POST /tasks/ask` inside same group                |
| View          | `dashboard.blade.php` — Alpine.js + Blade components              | Add search bar partial, AJAX submit                    |
| Validation    | `StoreTaskRequest` — `prepareForValidation()` pattern             | New `AiRecallRequest` with query validation            |
| Service layer | None (fat controllers)                                            | Create `AiRecallService` for Prism calls               |
| Tests         | Pest 4.7                                                          | `Prism::fake()` + feature tests                        |
| Frontend      | Alpine.js 3.x, `x-model`, `$dispatch()`                           | `x-model="aiQuery"`, fetch to `/tasks/ask`             |

**Lesson from `lessons.md`**: New routes must go inside the existing `Route::middleware('auth')->group()` block
(not inline `->middleware('auth')`).

### 6. Library Comparison (from prior Exa research)

| Library                       | Stars | Laravel 13             | OpenRouter Native      | Testing API     | Risk                |
|-------------------------------|-------|------------------------|------------------------|-----------------|---------------------|
| **prism-php/prism** ⭐         | 2334  | ✅ 11-13                | ✅ Provider::OpenRouter | ✅ Prism::fake() | Community pkg       |
| laravel/ai                    | —     | ✅ 12+                  | ⚠️ Custom base URL     | ❓ Unknown       | PHP 8.3+ req, young |
| moe-mizrak/laravel-openrouter | —     | ⚠️ 10+ (unverified 13) | ✅ Direct wrapper       | ❌ No fake()     | OpenRouter lock-in  |
| mozex/anthropic-laravel       | 70    | ✅ 11+                  | ❌ Anthropic direct     | ✅ fake()        | No OpenRouter       |

**Prism PHP wins** on: community size, Laravel 13 explicit support, native OpenRouter provider, `Prism::fake()`
testing, Blade view system prompts, upgrade path to RAG (moneo/laravel-rag builds on Prism).

## Code References

- `composer.json:9-10` — PHP ^8.4, Laravel ^13.8
- `app/Http/Controllers/TaskController.php:12-17` — index() with user scoping + pagination
- `app/Models/Task.php:25-28` — `scopeForUser()` query scope
- `database/migrations/2026_05_26_123431_create_tasks_table.php:21` — composite index `[user_id, task_date]`
- `routes/web.php:12-20` — auth middleware group
- `resources/views/dashboard.blade.php:20-28` — task list header (AI search bar insertion point)
- `app/Http/Requests/StoreTaskRequest.php:31-38` — validation pattern to follow
- `resources/js/app.js:1-8` — Alpine.js setup
- `context/foundation/tech-stack.md:19,25` — OpenRouter + grounding architecture decisions
- `context/foundation/prd.md:85-86,98-99,102` — FR-009, FR-010, NFRs

## Architecture Insights

1. **Context injection, not RAG**: The task count per user is bounded — full history fits in a system prompt.
   No embeddings or vector store needed for MVP.
2. **Blade-powered prompts**: Prism's `withSystemPrompt(view(...))` lets us use Blade templates for prompt
   engineering — version-controlled, testable, with full Blade syntax.
3. **Service layer gap**: Current architecture uses fat controllers. S-02 should introduce
   `AiRecallService` as the first service class — keeps controller thin, isolates Prism dependency.
4. **Existing composite index** on `[user_id, task_date]` is ideal for fetching user tasks ordered by date
   for context injection.

## Historical Context

- `context/changes/auth-scaffold/` — F-01 done, auth middleware in place
- `context/changes/task-log-core/` — S-01 done, Task model + controller + routes + views implemented (PR #16)
- No prior AI integration attempts in the codebase

## Open Questions

1. **Prompt engineering strategy**: How to structure the system prompt to ensure grounding (no hallucinated
   dates)? — To be decided during `/10x-plan`. The Prism docs show Blade views work as system prompts,
   so we can iterate on prompt templates without code changes.
2. **Task history limit**: Should we cap the number of tasks injected as context (e.g., last 100)?
   Token budget depends on model choice via OpenRouter.
3. **Streaming vs sync**: MVP can use synchronous `asText()`. Streaming (`asStream()`) is available as a
   future enhancement if perceived latency matters.
4. **Security risks** : Prompt injection, do we need some kind of guardialis? 

