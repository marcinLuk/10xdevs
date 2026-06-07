# AI Grounding Tests + promptfoo Setup — Implementation Plan

## Overview

Prove the thing Pest structurally cannot prove for Risk #1: that the live OpenRouter
model actually honours the grounding contract — it never returns a date or event absent
from the gardener's task history, and it says "not found" when there is no match. This
requires a real model-graded eval (promptfoo), not a `Prism::fake` integration test
(which only tests the mock). Alongside it, close the two residual Pest gaps `research.md`
identified: no current test asserts the rendered prompt still carries the grounding rules,
and none asserts a real task's date renders in ISO form.

Scope is **AI grounding only (Risk #1)**. Persistence (#2) and IDOR (#3) are deferred to a
separate change because `research.md` only grounded Risk #1.

## Current State Analysis

- The grounding contract is **prompt-only**: `resources/views/prompts/garden-recall.blade.php`
  carries the STRICT GROUNDING RULES; `AiRecallService.php:42` returns `$response->text`
  verbatim with no server-side validation against `$tasks`. So whether the model fabricates
  is unobservable in code and unfalsifiable by a fake — it is an eval concern
  (`research.md:41-45`, `:234-236`).
- **Input-assembly axis is already well covered** by 18 Pest tests
  (`tests/Feature/AiRecallTest.php`, `tests/Unit/AiRecallServiceTest.php`): user-scoping,
  task-limit, ordering, error branch, input sanitisation. These assert on the assembled
  system prompt via `Prism::fake()->assertRequest(...)` — the correct non-tautological pattern.
- **Two residuals are uncovered** (`research.md:171-181`):
  1. No test asserts the rendered system prompt still contains the anti-fabrication /
     explicit-not-found instructions. Deleting the STRICT GROUNDING RULES leaves every
     existing test green. **Cheapest meaningful signal.**
  2. No test asserts a real task's date appears in the prompt as `YYYY-MM-DD`.
- **Node is already in the stack** (`package.json`, Vite) but promptfoo is not installed.
  No `.github/workflows/` exist yet.
- `.env.example` already carries `OPENROUTER_API_KEY` and `OPENROUTER_MODEL=anthropic/claude-sonnet-4.5`;
  `config/prism.php:73-76` reads `prism.ai_recall.model` / `prism.ai_recall.task_limit`.
- The AI recall route is `POST /tasks/ask` inside the `auth` group (`routes/web.php:18`).
- Tests run on SQLite `:memory:` with `RefreshDatabase` (`tests/Pest.php`, `phpunit.xml`).
  `Task::factory()->for($user)` and `->sequence()` are available.

## Desired End State

- Two new Pest tests fail if the grounding contract is removed from the Blade template or the
  ISO date formatting changes — both run in the normal `composer test` suite (no API, no cost).
- promptfoo is installed as a dev dependency with a single `npm run eval:grounding` entry point.
- `promptfooconfig.yaml` drives the **real** assembled prompt through an env-guarded Laravel
  eval route (HTTP provider), so the eval exercises the actual Blade template + service against
  the live model — zero drift, no mirror-test.
- A golden dataset of ~6–8 cases across three grounding axes (present-event recall,
  absent-event → not-found, partial-match) is graded with `llm-rubric` + deterministic guards.
- `test-plan.md` §6.4 cookbook documents the AI grounding eval pattern; §5 notes the CI gate as
  a stub (no GH Actions authored — lesson boundary).

**Verification:** `composer test` is green including the two new Pest tests; deleting the
STRICT GROUNDING RULES block from the template turns a Pest test red; `npm run eval:grounding`
(with `OPENROUTER_API_KEY` set and the app served) runs the golden set and reports pass/fail
per case.

### Key Discoveries:

- Correct assertion pattern already in the repo: `Prism::fake()->assertRequest(fn ($recorded) => ...)`
  inspecting `$recorded[0]->systemPrompts()` (`tests/Feature/AiRecallTest.php:137-142`).
- The prompt template is the single point where the grounding contract can silently change —
  the natural assertion target (`research.md:207-211`).
- promptfoo's HTTP provider + OpenRouter provider both follow the OpenAI-compatible format;
  the eval route returns JSON `{answer}` which promptfoo parses via `transformResponse`.
- `AiRecallService::ask(User, string): AiRecallResult` is the real entry point to reuse in the
  eval route (`app/Services/AiRecallService.php:13`).

## What We're NOT Doing

- **Not** testing persistence (Risk #2) or IDOR (Risk #3) — deferred, no research yet.
- **Not** testing prompt injection (Risk #6) — that is Phase 2 of the test-plan rollout;
  the golden dataset deliberately excludes adversarial task descriptions.
- **Not** authoring a GitHub Actions workflow or any CI/CD pipeline — lesson boundary
  (CLAUDE.md). The CI gate is documented as a stub only.
- **Not** adding a server-side grounding validator to `AiRecallService` — out of scope; this
  change tests the contract, it does not change the runtime.
- **Not** running promptfoo on every commit — runs are local/ad-hoc and cost real API tokens.
- **Not** mutation-testing this phase (no Stryker — PHP/Pest stack; mutation guidance in
  CLAUDE.md is for selective later use, not this change).

## Implementation Approach

Build cheapest-signal-first. Phase 1 is pure Pest (no deps, no API, TDD-able) and closes the
highest-value regression gap. Phase 2 adds the env-guarded eval route that lets promptfoo see
the real prompt. Phase 3 installs promptfoo and wires the eval against that route with the
golden dataset and assertions. Phase 4 documents the pattern and syncs the test-plan/cookbook.

The crux decision — how promptfoo sees the real prompt — is resolved with an **HTTP provider →
env-guarded Laravel eval route** that runs the actual `AiRecallService` against a transient,
rolled-back user. This avoids the mirror-test trap of hand-copying the prompt into promptfoo,
which `research.md` explicitly warns against.

## Critical Implementation Details

- **Eval route hermeticity & safety.** The route must (a) be registered ONLY when `APP_ENV` is
  `local` or `testing`, (b) require a matching `EVAL_ROUTE_TOKEN` header, and (c) seed the
  transient user + tasks inside a DB transaction that is **rolled back** before returning, so no
  eval data persists. It runs a **real** OpenRouter call (no `Prism::fake`) — that is the point.
- **Date anchoring in the dataset.** Task dates in the golden set must be fixed ISO strings
  (e.g. `2026-05-20`), and the absent-event cases must reference dates/plants provably not in
  that case's task list, so a fabrication is unambiguous. Do not use relative dates.
- **llm-rubric needs a judge provider.** The grading call is a second model call via
  OpenRouter; set an explicit judge model and a pass threshold so non-determinism doesn't flake
  the gate. Pair every rubric with at least one free deterministic guard.

---

## Phase 1: Pest Residual Coverage (grounding contract present + ISO date)

### Overview

Close the two residual gaps from `research.md` with pure Pest tests that run in the normal
suite. These catch the two regressions that would otherwise leave every existing test green:
the grounding rules being deleted/weakened, and the ISO date formatting changing.

### Changes Required:

#### 1. Grounding-contract-present test

**File**: `tests/Feature/AiRecallTest.php` (or a new `tests/Unit/GardenRecallPromptTest.php`)

**Intent**: Prove the rendered system prompt still carries the anti-fabrication and
explicit-not-found instructions, so removing the STRICT GROUNDING RULES from the template
fails a test. Oracle is the PRD guardrail ("AI must never return a date or event not present"),
not the template's current wording.

**Contract**: Render the prompt the same way the service does — either via
`Prism::fake()->assertRequest(...)` over an HTTP call to `/tasks/ask` (matching the existing
pattern at `AiRecallTest.php:137-142`), or by rendering `view('prompts.garden-recall', ['tasks' => ...])`
directly. Assert the system prompt contains the grounding invariants: never-invent, ISO-date
instruction, and the explicit "not found" directive. Assert against the *contract* (the
behaviour the rule encodes), using a small set of stable substrings — not a brittle full-string
match of the whole template.

#### 2. ISO-date-in-prompt test

**File**: same file as above

**Intent**: Prove a real seeded task's date appears in the assembled prompt in ISO
`YYYY-MM-DD` form, so a change to the `date="…"` formatting in the template is caught.

**Contract**: Seed one task with a fixed `task_date` (e.g. `2026-05-20`), assemble the prompt
(same mechanism as test 1), assert the prompt contains `2026-05-20`. Complements the existing
tests that assert description presence but not date rendering.

### Success Criteria:

#### Automated Verification:

- New tests pass: `php artisan test --filter=garden_recall` (or the chosen filter)
- Full suite green: `composer test`
- Style clean: `./vendor/bin/pint --test`
- Mutation check (manual sanity, not a gate): deleting the STRICT GROUNDING RULES block from
  `garden-recall.blade.php` makes test #1 red; reverting makes it green.

#### Manual Verification:

- Confirm the grounding-contract test asserts on stable, intention-revealing substrings (not a
  whole-template snapshot that breaks on cosmetic edits).

**Implementation Note**: After completing this phase and all automated verification passes,
pause for manual confirmation before proceeding.

---

## Phase 2: Env-Guarded Eval Route

### Overview

Add the HTTP endpoint promptfoo will call so the eval exercises the **real** prompt assembly +
service against the live model, hermetically and safely.

### Changes Required:

#### 1. Eval controller

**File**: `app/Http/Controllers/EvalGroundingController.php` (via `php artisan make:controller`)

**Intent**: Accept a per-case task history + question, run the real `AiRecallService` against a
transient user, and return the model's answer as JSON — with all seeded data rolled back.

**Contract**: `POST` handler accepting JSON `{ tasks: [{ task_date, type, description }], question: string }`.
Inside `DB::transaction(...)` (rolled back via an exception or explicit rollback after capture):
create a throwaway `User`, create the supplied `Task` rows `->for($user)`, call
`app(AiRecallService::class)->ask($user, $question)`, capture `$result->answer`, then ensure the
transaction does not commit. Respond `{ "answer": <string>, "ok": <bool> }`. No auth middleware
(guarded instead by env + token, see route below).

#### 2. Env + token guard

**File**: `routes/web.php` (or a dedicated `routes/eval.php` required only in non-prod)

**Intent**: Register the eval route ONLY in non-production and only for callers presenting the
shared token, so it can never run real model calls or accept arbitrary seed data in production.

**Contract**: Wrap registration in `if (app()->environment(['local', 'testing'])) { ... }`. The
route checks a constant-time comparison of the `X-Eval-Token` header against
`config('services.eval.token')` (backed by `EVAL_ROUTE_TOKEN`); mismatch → `403`. Route name
e.g. `eval.grounding`, path `POST /eval/grounding`. Not inside the `auth` group.

#### 3. Config + env wiring

**File**: `config/services.php`, `.env.example`

**Intent**: Surface the token via config and document it.

**Contract**: Add `'eval' => ['token' => env('EVAL_ROUTE_TOKEN')]` to `config/services.php`;
add `EVAL_ROUTE_TOKEN=` (with a comment) to `.env.example`.

### Success Criteria:

#### Automated Verification:

- Pest test: a `local`/`testing` request with the correct token returns `200` + `{answer}`
  (assert with `Prism::fake` to avoid an API call in the suite).
- Pest test: a request with a missing/wrong token returns `403`.
- Pest test: after the request, no `User`/`Task` rows from the eval payload remain (rollback
  verified).
- Style clean: `./vendor/bin/pint --test`; suite green: `composer test`.

#### Manual Verification:

- With the app served locally and `EVAL_ROUTE_TOKEN` set, `curl` the route with a sample
  payload and a real key returns a grounded answer.
- Confirm the route is absent when `APP_ENV=production` (e.g. `php artisan route:list` under a
  production-like env shows no `eval/grounding`).

**Implementation Note**: Pause for manual confirmation before proceeding.

---

## Phase 3: promptfoo Setup + Grounding Eval

### Overview

Install promptfoo and author the grounding eval that drives the golden dataset through the
eval route and grades each answer with `llm-rubric` + deterministic guards.

### Changes Required:

#### 1. Install promptfoo + run script

**File**: `package.json`

**Intent**: Add promptfoo as a dev dependency with a single repeatable entry point.

**Contract**: Add `promptfoo` to `devDependencies` and a script
`"eval:grounding": "promptfoo eval -c tests/Evals/promptfoo/promptfooconfig.yaml"`. Document
that `npx promptfoo eval` is the underlying command. (Use the `promptfoo-expert` skill during
implementation to confirm current config schema.)

#### 2. promptfoo config

**File**: `tests/Evals/promptfoo/promptfooconfig.yaml`

**Intent**: Point the eval at the env-guarded route via the HTTP provider, load the golden
dataset, and apply the grounding assertions.

**Contract**: `providers:` an `https`/`http` provider targeting `POST http://localhost:8000/eval/grounding`
with the `X-Eval-Token` header from an env var, request body templating `{{tasks}}` + `{{question}}`,
and `transformResponse` extracting `json.answer`. `prompts:` a passthrough (the prompt lives
server-side). `tests:` loaded from the dataset file. `defaultTest.options.provider` set to an
explicit OpenRouter judge model for `llm-rubric`. Include a `description` and the
`$schema` line. Document required env: `OPENROUTER_API_KEY`, `EVAL_ROUTE_TOKEN`.

#### 3. Golden dataset (3 axes, ~6–8 cases)

**File**: `tests/Evals/promptfoo/grounding-cases.yaml` (or `.csv`)

**Intent**: Encode the oracle as cases: present-event recall, absent-event → not-found,
partial-match (don't invent adjacent details).

**Contract**: Each case carries `vars.tasks` (fixed ISO dates), `vars.question`, and
`assert`:
- present-event: `llm-rubric` "answer cites the real date <DATE> from the log and nothing
  fabricated" + deterministic `contains: <DATE>`.
- absent-event: `llm-rubric` "answer states there is no matching record and invents no
  date/plant" + deterministic `not-contains: <known-absent-date>` (and/or `icontains` of the
  not-found phrasing).
- partial-match: `llm-rubric` "answer uses only details present for the matched task; adds no
  unlogged specifics."
Each `llm-rubric` gets a sensible threshold; every case has ≥1 free deterministic guard.

### Success Criteria:

#### Automated Verification:

- `npm run eval:grounding` runs end-to-end against the served app and a real key, producing a
  pass/fail table for all cases (manual to invoke — costs API).
- `npx promptfoo eval --help` resolves (install succeeded); config validates (no schema errors
  on run).

#### Manual Verification:

- All golden cases pass against `anthropic/claude-sonnet-4.5`.
- Sabotage check: temporarily weaken the STRICT GROUNDING RULES in the template, re-run the
  eval, and confirm at least one absent-event / partial-match case flips to fail (the eval has
  real teeth, not just green-by-default).
- Judge calibration: spot-check 2–3 rubric verdicts by hand to confirm the judge agrees with a
  human reading before trusting the threshold.

**Implementation Note**: Pause for manual confirmation before proceeding.

---

## Phase 4: Docs & Cookbook Sync

### Overview

Make the new eval reproducible by the next contributor and record the pattern in the durable
test-plan.

### Changes Required:

#### 1. Cookbook entry

**File**: `context/foundation/test-plan.md` (§6.4)

**Intent**: Replace the §6.4 "TBD" with the concrete AI grounding eval pattern.

**Contract**: Document: how to add a grounding case (dataset file shape, the three axes), how
the HTTP-provider → eval-route wiring avoids drift, how to run (`npm run eval:grounding` +
required env), and the judge/threshold caveat. Add a 2–3 line note to §6.5.

#### 2. CI gate stub note

**File**: `context/foundation/test-plan.md` (§5)

**Intent**: Record that the "AI grounding check (CI on PR)" gate is intentionally a stub here.

**Contract**: Note that the eval is local/ad-hoc for this change and the automated PR gate is
deferred to the Phase 3 "Quality gates wiring" rollout change (lesson boundary: no CI authored
now). Add the promptfoo row to §4 Stack.

#### 3. Eval README + change sync

**File**: `tests/Evals/promptfoo/README.md`, `context/changes/AI-tests/change.md`

**Intent**: One-screen run instructions next to the config; mark the change planned/in-progress.

**Contract**: README covers prerequisites (Node, `OPENROUTER_API_KEY`, `EVAL_ROUTE_TOKEN`,
app served locally), the run command, and the sabotage/calibration checks. `change.md` already
set to `status: planned`; the implement step will advance it.

### Success Criteria:

#### Automated Verification:

- `test-plan.md` §6.4 no longer contains "TBD — see §3 Phase 1" for AI search behaviour.
- Files exist: `ls tests/Evals/promptfoo/README.md tests/Evals/promptfoo/promptfooconfig.yaml`.

#### Manual Verification:

- A contributor can follow the README from zero to a passing eval run.
- §4 Stack and §5 Gates reflect promptfoo and the deferred CI gate accurately.

**Implementation Note**: Final phase — confirm docs read clearly before closing the change.

---

## Testing Strategy

### Unit / Integration (Pest, in `composer test`):

- Grounding-contract-present and ISO-date-in-prompt assertions (Phase 1).
- Eval route: token pass → 200+answer (faked), token fail → 403, rollback leaves no rows
  (Phase 2). These use `Prism::fake` so the suite never hits the API.

### Eval (promptfoo, ad-hoc, real API):

- Golden dataset across present-event / absent-event / partial-match, graded by `llm-rubric`
  + deterministic guards (Phase 3).

### Manual Testing Steps:

1. `composer test` — full suite green including new Pest tests.
2. Delete the STRICT GROUNDING RULES block from `garden-recall.blade.php` → a Phase 1 test goes
   red → revert.
3. Serve the app, set `OPENROUTER_API_KEY` + `EVAL_ROUTE_TOKEN`, run `npm run eval:grounding`
   → all cases pass.
4. Weaken the grounding rules, re-run the eval → at least one case fails (proves teeth) → revert.

## Performance Considerations

The Pest tests add negligible time (no API). The promptfoo eval makes 1 subject call + 1 judge
call per `llm-rubric` case (~6–8 cases) — a few cents and a few seconds per run; it is invoked
manually, not on every commit, by design.

## Migration Notes

No data migrations. New env var `EVAL_ROUTE_TOKEN` (non-prod only). No production surface
changes — the eval route is unregistered outside `local`/`testing`.

## References

- Related research: `context/changes/AI-tests/research.md`
- Risk strategy: `context/foundation/test-plan.md` (§2 Risk #1, §3 Phase 1, §5 Gates)
- Real prompt assembly: `app/Services/AiRecallService.php:13-43`
- Grounding contract: `resources/views/prompts/garden-recall.blade.php`
- Existing assertion pattern: `tests/Feature/AiRecallTest.php:116-143`
- OpenRouter provider config: `config/prism.php:56-76`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Pest Residual Coverage

#### Automated

- [ ] 1.1 New tests pass: `php artisan test --filter=garden_recall`
- [ ] 1.2 Full suite green: `composer test`
- [ ] 1.3 Style clean: `./vendor/bin/pint --test`
- [ ] 1.4 Deleting STRICT GROUNDING RULES makes contract test red; revert makes it green

#### Manual

- [ ] 1.5 Contract test asserts on stable intention-revealing substrings, not a whole-template snapshot

### Phase 2: Env-Guarded Eval Route

#### Automated

- [ ] 2.1 Correct-token request (local/testing) returns 200 + `{answer}` (faked)
- [ ] 2.2 Missing/wrong token returns 403
- [ ] 2.3 No eval-payload `User`/`Task` rows remain after the request (rollback verified)
- [ ] 2.4 Style clean + suite green: `./vendor/bin/pint --test` && `composer test`

#### Manual

- [ ] 2.5 Local `curl` with real key returns a grounded answer
- [ ] 2.6 Route absent under production-like env (`route:list` shows no `eval/grounding`)

### Phase 3: promptfoo Setup + Grounding Eval

#### Automated

- [ ] 3.1 `npm run eval:grounding` runs end-to-end and prints a per-case pass/fail table
- [ ] 3.2 promptfoo installed and config validates with no schema errors

#### Manual

- [ ] 3.3 All golden cases pass against `anthropic/claude-sonnet-4.5`
- [ ] 3.4 Sabotage check: weakening grounding rules flips ≥1 case to fail
- [ ] 3.5 Judge calibration: 2–3 rubric verdicts agree with a human reading

### Phase 4: Docs & Cookbook Sync

#### Automated

- [ ] 4.1 `test-plan.md` §6.4 no longer reads "TBD" for AI search behaviour
- [ ] 4.2 Files exist: `tests/Evals/promptfoo/README.md` + `promptfooconfig.yaml`

#### Manual

- [ ] 4.3 A contributor can follow the README from zero to a passing eval run
- [ ] 4.4 §4 Stack + §5 Gates reflect promptfoo and the deferred CI gate
