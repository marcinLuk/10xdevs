# AI Grounding Tests + promptfoo Setup — Plan Brief

> Full plan: `context/changes/AI-tests/plan.md`
> Research: `context/changes/AI-tests/research.md`

## What & Why

Prove what Pest cannot: that the live OpenRouter model actually honours the grounding
contract — it never returns a date or event absent from the gardener's task history, and says
"not found" when there is no match. A `Prism::fake` test only tests the mock, so this needs a
real model-graded eval (promptfoo). We also close the two residual Pest gaps `research.md`
flagged for Risk #1.

## Starting Point

The grounding contract is prompt-only (`garden-recall.blade.php`); `AiRecallService` returns the
model's text verbatim with no server-side check. 18 Pest tests already cover the input-assembly
axis (scoping, limit, ordering) via `Prism::fake()->assertRequest`, but nothing asserts the
grounding rules are still in the template, nothing asserts ISO-date rendering, and nothing
exercises the live model. Node is in the stack; promptfoo is not installed; no CI workflows yet.

## Desired End State

Two new Pest tests go red if the grounding rules are deleted or the date format changes (run in
`composer test`, no API). promptfoo is installed with `npm run eval:grounding`, driving a golden
set of ~6–8 cases through an env-guarded Laravel eval route — so the eval exercises the **real**
Blade prompt + service against the live model, graded by `llm-rubric` + deterministic guards.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
| --- | --- | --- | --- |
| How promptfoo sees the real prompt | HTTP provider → env-guarded Laravel eval route | Tests the actual end-to-end grounding path, zero drift — avoids the mirror-test trap research warns against | Plan |
| Change scope | AI grounding only (Risk #1) | Matches research, which grounded only Risk #1; #2/#3 have no research yet | Plan |
| Assertion strategy | `llm-rubric` + deterministic guards | Catches semantic fabrication while keeping free, fast regression checks | Plan |
| Golden dataset | 3 axes, ~6–8 cases (present / absent / partial) | Directly maps to the research oracle; cheap to run; injection deferred to Risk #6 | Plan |
| Run cadence | Local `npm` script + documented CI stub | Respects the "don't author CI/CD" lesson boundary; keeps API spend intentional | Plan |
| Eval route contract | Stateless body + transient user, rolled-back tx, env+token guarded | Hermetic per case, no prod surface, no leftover data | Plan |

## Scope

**In scope:** two residual Pest tests (grounding-rules-present, ISO-date); env-guarded eval
route; promptfoo install + config + golden dataset; cookbook/test-plan docs.

**Out of scope:** persistence (#2) and IDOR (#3) tests; prompt injection (#6); a runtime
grounding validator; any GitHub Actions / CI pipeline; per-commit eval runs.

## Architecture / Approach

`promptfoo eval` → HTTP provider POSTs `{tasks, question}` (+ `X-Eval-Token`) to a non-prod-only
`POST /eval/grounding` route → controller seeds a transient user + tasks in a rolled-back
transaction, runs the real `AiRecallService` (real Blade prompt + real OpenRouter call), returns
`{answer}` → promptfoo grades the answer with `llm-rubric` (OpenRouter judge) + deterministic
`contains`/`not-contains` guards. Pest tests (Phase 1) stay pure and API-free.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Pest residuals | Grounding-rules-present + ISO-date tests in `composer test` | Asserting a brittle whole-template snapshot instead of intent |
| 2. Eval route | Env+token-guarded `POST /eval/grounding`, rolled-back tx | Leaking a real-API/seed endpoint into production |
| 3. promptfoo eval | Install + config + golden dataset + assertions | Flaky judge without a threshold + deterministic backstop |
| 4. Docs & cookbook | test-plan §6.4 pattern, §5 CI stub, eval README | — |

**Prerequisites:** Node/npm; `OPENROUTER_API_KEY` and a new `EVAL_ROUTE_TOKEN`; app served
locally for eval runs.
**Estimated effort:** ~2 sessions across 4 phases (Phase 1 is quick/TDD-able; Phase 3 needs
judge calibration).

## Open Risks & Assumptions

- `llm-rubric` is non-deterministic; relies on a sensible threshold + deterministic guards and a
  one-time judge calibration against human reading.
- Eval route safety depends on the env check AND the token — both must hold; a Pest test asserts
  the 403 path.
- Eval runs cost real API tokens; intentionally manual, not gated in CI this change.

## Success Criteria (Summary)

- Removing the grounding rules from the template turns a Pest test red.
- `npm run eval:grounding` runs all golden cases and they pass on the configured model.
- Weakening the grounding rules flips ≥1 eval case to fail (the eval has teeth).
