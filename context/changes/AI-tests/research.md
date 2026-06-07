---
date: 2026-06-01T15:46:11+0200
researcher: Claude (Opus 4.8)
git_commit: cef71087275b07f3bca4cbeb8d32fd1ff3ffddce
branch: feat/tests
repository: marcinLuk/10xdevs
topic: "Risk #1 — AI grounding: AI must never return a date/event not in the gardener's task history"
tags: [ research, codebase, ai-recall, grounding, prism, integration-test, risk-1 ]
status: complete
last_updated: 2026-06-01
last_updated_by: Claude (Opus 4.8)
---

# Research: Risk #1 — AI Grounding (no fabricated dates/events)

**Date**: 2026-06-01T15:46:11+0200
**Researcher**: Claude (Opus 4.8)
**Git Commit**: cef71087275b07f3bca4cbeb8d32fd1ff3ffddce
**Branch**: feat/tests
**Repository**: marcinLuk/10xdevs

## Research Question

Ground the oracle for **Phase 1 / Risk #1** from `context/foundation/test-plan.md`:
> "AI returns a date or event not in the gardener's task history — user acts on fabricated information."

The test plan scopes this risk to an **integration** layer (HTTP test with `Prism::fake` for the AI call, real DB for
task data) and explicitly warns against the tautology of *asserting against the faked AI response itself*. This research
maps the AI recall flow, establishes what behaviour a test must prove (from PRD guardrails, not from implementation
shape), and separates what is **already covered** from the **genuine residual gap**.

## Summary

The AI recall feature is a thin controller over `AiRecallService`, which loads the authenticated user's tasks, renders
them into a Blade-templated system prompt that carries explicit grounding rules, and calls OpenRouter via Prism. **The
flow is well-built and already has substantial test coverage** (18 tests across `tests/Feature/AiRecallTest.php` and
`tests/Unit/AiRecallServiceTest.php`).

The decisive oracle finding has two faces:

1. **The genuine grounding protection is prompt-only and unverifiable in code.** The service returns `$response->text`
   verbatim (`app/Services/AiRecallService.php:42`); there is no server-side check that the model's answer references
   only dates/events present in `$tasks`. Whether the live model actually fabricates is an **eval / promptfoo concern,
   not an integration test**. An integration test that fakes a fabricated answer and asserts it is passed through proves
   only that there is no server guard — it does **not** prove grounding.

2. **What an integration test *can* meaningfully prove for Risk #1 is the prompt/context assembly** — that the model
   receives exactly the user's real tasks (and only theirs), in ISO date format, wrapped in the grounding contract. This
   is the non-tautological axis the test plan points at, and most of it is **already covered**. The one high-signal
   regression that would **silently pass today** is **deletion or weakening of the grounding rules in the prompt
   template** — no existing test asserts the rendered prompt still contains the anti-fabrication / "say so explicitly"
   instructions.

**Bottom line for planning:** Risk #1's input-assembly axis is largely green. The residual, worth-writing tests are: (a)
assert the rendered system prompt still carries the grounding contract; (b) assert the real task's date appears in the
prompt in ISO `YYYY-MM-DD` form. Everything else is either already covered or belongs to an eval harness, not an
integration test.

## Detailed Findings

### Route & entry point

- `routes/web.php:18` — `Route::post('/tasks/ask', [AiRecallController::class, 'ask'])->name('tasks.ask');`
    - **POST** `/tasks/ask`, name `tasks.ask`, inside the `auth` middleware group (`routes/web.php:13`). No
      throttle/rate-limit middleware.
- Conforms to lessons.md rule "register new routes inside the existing auth middleware group."

### Controller (thin)

`app/Http/Controllers/AiRecallController.php:11-20`:

```php
public function ask(AiRecallRequest $request, AiRecallService $service): JsonResponse
{
    $result = $service->ask($request->user(), $request->validated()['question']);
    return response()->json([
        'ok' => $result->ok,
        'answer' => $result->answer,
        'error' => $result->error,
    ]);
}
```

Returns **JSON** (`{ok, answer, error}`), not a Blade view.

### Service — context assembly (the testable core)

`app/Services/AiRecallService.php`:

- **Loads history (scoped):** `:18-21`
  ```php
  $tasks = $user->tasks()
      ->orderBy('task_date', 'desc')
      ->limit($limit)
      ->get();
  ```
  `$user` is `$request->user()` (authenticated). `User::tasks()` is `hasMany(Task::class)` (
  `app/Models/User.php:33-36`) → automatically constrained by `user_id`. **Scoping is correct.**
- **`$limit`** from `prism.ai_recall.task_limit`, default **50** (`config/prism.php:75`; `AiRecallService.php:15`).
- **Renders prompt:** `:23` — `view('prompts.garden-recall', ['tasks' => $tasks])->render();`
- **Calls provider:** `:26-32` —
  `Prism::text()->using(Provider::OpenRouter, $model)->withSystemPrompt($systemPrompt)->withPrompt('<user_question>'.$question.'</user_question>')->withClientOptions(['timeout'=>10])->withClientRetry(2,100)->asText();`
- **Returns:** `:42` — `AiRecallResult::success($response->text)` (verbatim, no post-processing). On `PrismException`:
  `:33-40` logs a warning and returns
  `AiRecallResult::error('The AI service is temporarily unavailable. Please try again.')`.

### System prompt — grounding contract is PRESENT and explicit

`resources/views/prompts/garden-recall.blade.php` carries the entire grounding contract the PRD guardrail demands:

- "based **ONLY** on the structured task log below"
- "**NEVER** invent dates, plants, task types, or events that are not present in the data"
- "If the data does not contain a matching task, say so explicitly (e.g., \"I don't see any record of that in your
  log.\")"
- "Quote actual dates from the log… Use ISO format (YYYY-MM-DD)"
- DATA-ONLY injection guard wrapping `<task_entry>` / `<user_question>`
- Task rows rendered via `@forelse` as
  `<task_entry date="{{ $task->task_date->format('Y-m-d') }}" type="{!! $task->type !!}">{!! $task->description !!}</task_entry>`;
  empty branch renders `(no tasks logged yet)`.

> Note: task `type`/`description` are injected **unescaped** (`{!! !!}`). Mitigated at prompt level by the DATA-ONLY
> guard, but this is a prompt-injection surface — that is **Risk #6 / Phase 2**, out of scope here.

### Output rendering (why grounding is unverifiable on output)

- Frontend `resources/views/tasks/partials/ai-search.blade.php:80,84` renders the answer via Alpine **`x-text="answer"`
  ** → set as `textContent`, HTML-escaped (no XSS from AI output).
- Crucially, the answer is the model's verbatim text. **Nothing between the model and the screen validates the answer
  against `$tasks`.** This is why "does it fabricate" cannot be an integration assertion.

### Input validation

`app/Http/Requests/AiRecallRequest.php`: `authorize(): true` (relies on `auth` middleware); `prepareForValidation()`
does `trim(strip_tags($question))`; rules `question` = `required|string|min:5|max:500`.

### Prism config / faking

- Provider **OpenRouter**, model from `prism.ai_recall.model`, default `anthropic/claude-sonnet-4.5` (
  `config/prism.php:74`).
- Fake via `Prism::fake([TextResponseFake::make()->withText('...')])` (`Prism\Prism\Testing\TextResponseFake`).
- Inspect the assembled prompt: `$fake->assertRequest(fn(array $recorded) => ...)` where
  `$recorded[0]->systemPrompts()` (each `->content`) is the system prompt and `$recorded[0]->prompt()` is the user
  prompt. `$fake->assertCallCount(n)`.

## What is ALREADY covered (and how well)

| Behaviour                                                  | Test                                       | Verdict for Risk #1                                                                                                                        |
|------------------------------------------------------------|--------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| Only authenticated user's tasks reach the prompt (HTTP)    | `tests/Feature/AiRecallTest.php:116-143`   | **Strong** — asserts on assembled system prompt (input axis), not the fake output. Correct, non-tautological.                              |
| Only authenticated user's tasks reach the prompt (service) | `tests/Unit/AiRecallServiceTest.php:10-28` | **Strong** — same input-axis assertion.                                                                                                    |
| Old tasks beyond `task_limit` excluded from prompt         | `tests/Unit/AiRecallServiceTest.php:30-63` | **Strong** — newest-3 present, old absent. Covers the inverse-data surface.                                                                |
| Tasks ordered newest-first in prompt                       | `tests/Unit/AiRecallServiceTest.php:65-85` | Good — ordering of context.                                                                                                                |
| Guest blocked → redirect `/login`                          | `AiRecallTest.php:14-17`                   | Plumbing (access).                                                                                                                         |
| Provider failure → graceful `{ok:false,error:...}`         | `AiRecallTest.php:95-114`                  | Plumbing (error branch).                                                                                                                   |
| Question trimmed & `strip_tags`                            | `AiRecallTest.php:145-160`                 | Input sanitization.                                                                                                                        |
| Successful answer passes through                           | `AiRecallTest.php:19-41`                   | **Plumbing only.** Asserts faked text round-trips. **Not grounding proof** (the anti-pattern the plan names).                              |
| "No tasks" → grounded-style answer                         | `AiRecallTest.php:43-57`                   | **Plumbing only.** Asserts the faked "I don't see any record…" string. Proves the empty branch is reachable; **does not prove grounding.** |

## Oracle (what a Risk #1 test must prove — from sources, not implementation)

From `test-plan.md` Risk #1 row + PRD guardrail ("AI must never return a date or event not present"):

- **Provable by integration (input axis):** the system prompt the model receives contains (a) exactly the authenticated
  user's real tasks and no other user's, (b) those tasks' dates in ISO `YYYY-MM-DD`, and (c) the grounding contract (
  only-use-this-data + never-invent + explicit-not-found). The "not found" path must be reachable for an empty/no-match
  task set.
- **NOT provable by integration (and must not be faked into a false positive):** that the live model honours the
  contract and never fabricates. That is a model-eval/promptfoo concern. Asserting against a `Prism::fake` answer tests
  the mock, not grounding.

## Residual gaps worth a test (highest signal first)

1. **Grounding contract present in the rendered prompt.** No current test asserts the assembled system prompt still
   contains the anti-fabrication / explicit-not-found instructions. Deleting the STRICT GROUNDING RULES from
   `garden-recall.blade.php` would leave **every existing test green**. A test that renders the prompt (HTTP via
   `assertRequest`, or directly rendering the Blade view) and asserts it contains the grounding instructions catches the
   regression that most directly maps to Risk #1. **Cheapest meaningful signal.**
2. **Real task date appears in prompt in ISO form.** Assert the assembled prompt contains the seeded task's date as
   `YYYY-MM-DD` (the oracle requires the model be *able* to quote real dates; existing tests assert description
   presence, not the date rendering). Guards against a future change to the `date="…"` formatting in the template.

Lower priority / boundary:

3. Inverse at HTTP layer (real-but-old event beyond the 50-limit absent from prompt) — already covered at service
   layer (`AiRecallServiceTest.php:30-63`); an HTTP duplicate is low marginal signal.

## Code References

- `routes/web.php:18` — `tasks.ask` route inside `auth` group (`:13`)
- `app/Http/Controllers/AiRecallController.php:11-20` — thin controller, JSON response
- `app/Services/AiRecallService.php:18-21` — user-scoped task load
- `app/Services/AiRecallService.php:23` — prompt render
- `app/Services/AiRecallService.php:26-42` — Prism call + verbatim return + error branch
- `resources/views/prompts/garden-recall.blade.php` — grounding contract + task injection
- `app/Models/User.php:33-36` — `tasks()` hasMany
- `app/Http/Requests/AiRecallRequest.php` — validation + `strip_tags`
- `config/prism.php:73-76` — OpenRouter model + task_limit defaults
- `resources/views/tasks/partials/ai-search.blade.php:80,84` — `x-text` escaped output
- `tests/Feature/AiRecallTest.php` — 12 feature tests (see coverage table)
- `tests/Unit/AiRecallServiceTest.php` — 6 unit tests (see coverage table)
- `tests/Pest.php:18` — `RefreshDatabase` for Feature + Unit; `phpunit.xml:26-27` — SQLite `:memory:`
- `database/factories/TaskFactory.php`, `database/factories/UserFactory.php` — factories (support `->for($user)`,
  `->sequence()`)

## Architecture Insights

- **Controller-thin / service-fat:** all grounding logic lives in `AiRecallService` + the Blade prompt template, so the
  prompt template is the single point where the grounding contract can silently change. That makes the prompt template
  the natural assertion target.
- **Grounding is a prompt contract, not code.** Tests must assert on the *context sent to the model*, never on the
  *faked answer*. The existing service tests already model the correct pattern (`assertRequest` on `systemPrompts()`).
- **Test infra is ready:** Pest + RefreshDatabase + in-memory SQLite, working `Prism::fake` patterns, and Task/User
  factories. No setup phase needed for Risk #1 tests — this is a contract phase, a candidate for `/10x-tdd` (the first
  red test is nameable: *"the rendered recall prompt contains the never-invent grounding instruction"*).

## Historical Context (from prior changes)

- No prior change folders address AI recall testing; `context/changes/integration-test/` (this change) is the first.
  Risk strategy is fixed in `context/foundation/test-plan.md` (§2 Risk #1, §3 Phase 1).
- `context/foundation/lessons.md`: tests/artisan must run via `docker exec`; routes belong in the existing `auth`
  group (both already honoured by the code).

## Related Research

- None yet. This is the first research artifact for the test rollout. Phase 1 also covers Risk #2 (persistence) and Risk
  #3 (IDOR), deliberately out of scope for this Risk #1-only research per the user's instruction.

## Open Questions

1. **Scope of "fabrication":** PRD guardrail targets *false positives* (asserting a date/event that does not exist).
   Does Risk #1 also cover the *false negative* — a genuinely-logged event older than the 50-task limit being reported
   as "not found"? If yes, an HTTP-level inverse test is warranted; if the team treats it as recall-completeness (a
   separate concern), the service-level limit test already suffices. **Recommend confirming before planning.**
2. **Is live-model grounding tested anywhere?** It cannot be an integration test. If the team wants assurance the model
   honours the contract, that is a promptfoo/eval gate (referenced in CLAUDE.md mutation/eval guidance) — out of Phase
   1's integration scope. Flag for the test-plan owner.
