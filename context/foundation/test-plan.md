# Test Plan

> Phased test rollout for this project. Strategy is frozen at the top
> (§1–§5); cookbook patterns at the bottom (§6) fill in as phases ship.
> Read before writing any new test.
>
> Refresh: re-run `/10x-test-plan --refresh` when stale (see §8).
>
> Last updated: 2026-06-01

## 1. Strategy

Tests follow three non-negotiable principles for this project:

1. **Cost × signal.** The cheapest test that gives a real signal for the
   risk wins. Do not promote to e2e because e2e "feels safer." Do not put a
   vision model on top of a deterministic visual diff that already catches
   the regression.
2. **User concerns are first-class evidence.** Risks anchored in "the
   team is worried about X, and the failure would surface somewhere in
   area Y" carry the same weight as PRD lines or hot-spot data.
3. **Risks are scenarios, not code locations.** This plan documents *what
   could fail* and *why we believe it's likely* — drawn from documents,
   interview, and codebase *signal* (churn, structure, test base). It does
   NOT claim to know which line owns the failure. That knowledge is
   produced by `/10x-research` during each rollout phase. If the plan and
   research disagree about where the failure lives, research is the
   ground truth.

Hot-spot scope used for likelihood weighting: `app/`, `resources/`, `routes/`, `database/`, `config/`.

## 2. Risk Map

The top failure scenarios this project must protect against, ordered by
risk = impact × likelihood. Risks are failure scenarios in user / business
terms, not test names. The Source column cites the *evidence that surfaced
this risk* — never a specific file as "where the failure lives" (that is
research's job, see §1 principle #3).

| # | Risk (failure scenario)                                                                                                                    | Impact | Likelihood | Source (evidence — not anchor)                                                                               |
|---|--------------------------------------------------------------------------------------------------------------------------------------------|--------|------------|--------------------------------------------------------------------------------------------------------------|
| 1 | AI returns a date or event not in the gardener's task history — user acts on fabricated information                                        | High   | Medium     | PRD guardrails ("AI must never return a date or event not present"); interview Q1                            |
| 2 | Task silently fails to persist — gardener saves a task but it never appears in the list or vanishes across sessions                        | High   | Medium     | PRD guardrails ("Task data must never be silently lost"); PRD NFR (cross-session persistence)                |
| 3 | One gardener accesses another's tasks — IDOR on any task endpoint lets user A view/edit/delete/query user B's data                         | High   | Medium     | PRD access control ("single user, single account, no sharing"); abuse/security lens                          |
| 4 | Task CRUD validation bypass — empty or malformed data passes server-side validation, or XSS payload in task description renders in browser | Medium | Medium     | Interview Q4 ("Task CRUD validation — scariest gap"); PRD FR-006/007/008                                     |
| 5 | Shared Blade component change breaks functionality elsewhere — navigation, form buttons, or layout regression after template edit          | Medium | High       | Interview Q3 ("Blade templates / UI components"); hot-spot dir `resources/views/components` (20 commits/30d) |
| 6 | Prompt injection via task content — malicious task description hijacks AI system prompt, causing grounding bypass                          | Medium | Low        | PRD FR-010; roadmap S-02 ("prompt hardened against injection"); abuse/security lens                          |

### Risk Response Guidance

| Risk | What would prove protection                                                                                                                          | Must challenge                                                                                                             | Context `/10x-research` must ground                                                                                            | Likely cheapest layer                                                                             | Anti-pattern to avoid                                                                                   |
|------|------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| #1   | AI answer references only dates that exist in the gardener's task history; returns explicit "not found" when no match exists                         | "Happy-path test with Prism::fake proves grounding" — fake responses don't test the prompt/context assembly                | How task history is assembled into the prompt; what the system prompt instructs; how the AI response is rendered               | Integration (HTTP test with Prism::fake for the AI call, real DB for task data)                   | Asserting against the faked AI response itself (tautological — tests the mock, not the grounding logic) |
| #2   | A task saved via the create form appears in the task list on the next page load; persists after logout/login                                         | "Successful HTTP 200 on store means the task saved" — a 200 could mask a failed DB write if there's no redirect-and-verify | Task creation endpoint, validation rules, redirect target, how the list is populated                                           | Integration (HTTP test: POST → redirect → GET list → assert task present)                         | Asserting only the POST response status without verifying the task actually appears in the list         |
| #3   | User A cannot view, edit, delete, or query user B's tasks via any endpoint (direct URL, API, AI search)                                              | "Auth middleware is enough" — auth checks that you're logged in, not that you own the resource                             | All task endpoints (index, store, show, edit, update, destroy, AI search); how ownership is scoped (middleware vs query scope) | Integration (HTTP test: two users, user A tries user B's task IDs)                                | Testing only that unauthenticated requests are blocked (misses the IDOR — both users are authenticated) |
| #4   | Server rejects empty description, oversized input, and HTML/script tags in task fields; stored data is escaped on render                             | "Client-side validation prevents bad input" — server must validate independently of the client                             | Form request validation rules, Blade escaping mechanism, task field constraints                                                | Unit (validation rules) + Integration (HTTP test: submit bad data → assert 422)                   | Testing only that valid data is accepted (happy-path-only — never verifies the rejection path)          |
| #5   | Key pages (dashboard/task list, task create/edit forms, AI search) render with working form submission and navigation after a Blade component change | "The page loads with 200 OK" — a 200 with a broken form (missing submit button, broken action URL) is worse than a 500     | Which shared components are used on which pages; form action URLs; CSRF handling                                               | Integration (HTTP test: GET page → assert key elements present; POST form → assert success)       | Snapshot testing of full HTML output (breaks on every CSS change, catches nothing functional)           |
| #6   | A task description containing prompt-injection text does not cause the AI to ignore grounding instructions or fabricate data                         | "The prompt was hardened" — hardening is a claim until tested with adversarial input                                       | How task descriptions are injected into the prompt; what separates user data from system instructions                          | Integration (HTTP test: store task with injection payload → AI search → assert grounded response) | Testing only with benign task descriptions (never exercises the adversarial path)                       |

## 3. Phased Rollout

Each row is a discrete rollout phase that will open its own change folder
via `/10x-new`. Status moves left-to-right through the values below; the
orchestrator updates Status as artifacts appear on disk.

| # | Phase name                      | Goal (one line)                                                                                                 | Risks covered | Test types                             | Status      | Change folder |
|---|---------------------------------|-----------------------------------------------------------------------------------------------------------------|---------------|----------------------------------------|-------------|---------------|
| 1 | Critical-path + ownership       | Prove the AI recall loop is grounded, tasks persist, and users can't cross boundaries                           | #1, #2, #3    | integration (HTTP + DB)                | not started | —             |
| 2 | Validation + security hardening | Prove server-side validation rejects bad input, XSS is prevented, and prompt injection doesn't bypass grounding | #4, #5, #6    | unit (validation) + integration (HTTP) | not started | —             |
| 3 | Quality gates wiring            | Lock the test floor in CI so regressions can't merge                                                            | cross-cutting | CI gates (lint, Pint, test suite)      | not started | —             |

## 4. Stack

| Layer              | Tool                                | Version             | Notes                                                             |
|--------------------|-------------------------------------|---------------------|-------------------------------------------------------------------|
| unit + integration | Pest (PHPUnit backend)              | see `composer.lock` | Configured in `phpunit.xml`; SQLite `:memory:` for tests          |
| AI mocking         | Prism::fake                         | see `composer.lock` | Used in existing AI recall tests per roadmap S-02                 |
| AI grounding eval  | promptfoo (HTTP provider + OpenRouter judge) | see `package.json` | `tests/Evals/promptfoo/`; ad-hoc via `npm run eval:grounding` (real API); needs Node 22 LTS + app served on `:8001`; see §6.4 |
| e2e                | none yet — see Phase 3 if warranted | —                   | Not planned; integration tests cover critical paths at lower cost |
| lint + style       | Laravel Pint                        | see `composer.lock` | Run via `./vendor/bin/pint`                                       |

**Stack grounding tools (current session):**

- Docs: Context7 — available; checked: 2026-06-01
- Search: Exa.ai — available; checked: 2026-06-01
- Runtime/browser: Chrome browser automation (Claude in Chrome) — available for manual verification; not used for
  automated tests; checked: 2026-06-01
- Provider/platform: Linear MCP + GitHub CLI — available for issue tracking and CI gate setup; checked: 2026-06-01

## 5. Quality Gates

| Gate                      | Where      | Required?                 | Catches                                                      |
|---------------------------|------------|---------------------------|--------------------------------------------------------------|
| lint + style (Pint)       | local + CI | required                  | code style drift                                             |
| unit + integration (Pest) | local + CI | required after §3 Phase 1 | logic regressions, ownership violations, validation failures |
| AI grounding check        | local/ad-hoc (CI deferred) | stub — see note below | AI hallucination regression                                  |
| pre-merge test suite      | CI on PR   | required after §3 Phase 3 | all regressions blocked before merge                         |

> **AI grounding check is a stub here.** The promptfoo grounding eval shipped in
> change `AI-tests` runs **local/ad-hoc** (`npm run eval:grounding`) and costs real
> API tokens, so it is not wired into CI by this change. Authoring the automated
> PR gate is deferred to the §3 Phase 3 "Quality gates wiring" rollout change — no
> GitHub Actions workflow is created now (lesson boundary).

## 6. Cookbook Patterns

How to add new tests in this project. Each sub-section is filled in once
the relevant rollout phase ships; before that, the sub-section reads
"TBD — see §3 Phase N."

### 6.1 Adding a unit test

TBD — see §3 Phase 2 for validation rule unit tests.

### 6.2 Adding an integration test

TBD — see §3 Phase 1 for HTTP + DB integration test patterns (task persistence, AI grounding, IDOR checks).

### 6.3 Adding a test for a new task endpoint

TBD — see §3 Phase 1 for task endpoint ownership/persistence pattern and Phase 2 for validation/security pattern.

### 6.4 Adding a test for AI search behavior

AI search has **two** test layers — use both, for different questions:

**A. Prompt-assembly (Pest, in `composer test`, no API, no cost).** Proves the
context sent to the model is correct: the right user's tasks, ISO dates, and the
grounding contract present in the system prompt. Assert on the *input* axis, never
on a `Prism::fake` answer (that tests the mock).

- Render the prompt the way the service does: `view('prompts.garden-recall', ['tasks' => $tasks])->render()`, or inspect the assembled system prompt via `Prism::fake()->assertRequest(fn ($recorded) => ...)` over `$recorded[0]->systemPrompts()`.
- Example: `tests/Unit/GardenRecallPromptTest.php` asserts the rendered prompt carries the grounding invariants (`NEVER invent`, `say so explicitly`, `YYYY-MM-DD`, `source of truth`) and renders a real task date in ISO form. Deleting the STRICT GROUNDING RULES block from the template turns it red — that is the regression it guards.

**B. Live-model grounding (promptfoo, ad-hoc, real API).** Proves the *live model*
honours the contract — the thing Pest structurally cannot prove. Lives in
`tests/Evals/promptfoo/`.

- **No drift, no mirror-test:** the eval drives the *real* prompt assembly + `AiRecallService` through an env-guarded HTTP route (`POST /eval/grounding`, registered only under `local|testing`, token-gated, seeds a transient user + tasks and rolls back). promptfoo's HTTP provider calls that route, so there is no hand-copied prompt to drift from production. `transformResponse: json.answer`.
- **Adding a case** — append to `grounding-cases.yaml` under one of the three axes: *present-event* (cites the real `YYYY-MM-DD`), *absent-event* (explicit "not found", no fabricated date — keep the question date-free so a correct answer contains no ISO date), *partial-match* (uses only logged details; invents no variety/quantity). Use fixed ISO dates; make absent subjects provably absent from that case's log.
- **Oracle for the judge:** promptfoo sends the `llm-rubric` judge ONLY the output + the rubric, NOT the task log. **Embed the log in each rubric** with `{{ tasks | dump }}` so the judge grades grounding against the real source — otherwise it cannot distinguish a quoted-from-log detail from an invention and will false-fail correct answers. Pair every rubric with ≥1 free deterministic guard (`contains` the real date / `not-contains` / `not-icontains`) and a `threshold` (we use 0.7).
- **Run:** `npm run eval:grounding`, with the app served and these env set:
  - `OPENROUTER_API_KEY` — subject calls (via the route) and the judge.
  - `EVAL_ROUTE_TOKEN` — must match the app's value (sent as `X-Eval-Token`).
  - `EVAL_BASE_URL` — optional; defaults to `http://localhost:8001` (Docker/nginx). Set to `http://localhost:8000` if serving via `composer dev`.
- **Two environment gotchas (cost real time if missed):**
  - **Node 22 LTS** — Node 26's experimental fetch decompression terminates the judge's gzip'd OpenRouter response (`TypeError: terminated` on a 200). Run the eval on Node 20.20+/22/24, not 26.
  - **Port 8001** — the app serves on 8001 via Docker/nginx, not Laravel's 8000 default.
- **Trusting the gate:** spot-check a few `reason` verdicts by hand (judge calibration), and run a sabotage check — weaken the template's grounding rules, re-run, confirm ≥1 case flips to FAIL — before relying on it. See `tests/Evals/promptfoo/README.md`.

### 6.5 Per-rollout-phase notes

(After each phase lands, the final sub-phase appends a 2–3 line note here.)

- **Phase 1 / Risk #1 — AI grounding (change `AI-tests`, 2026-06-07).** Closed the
  two residual Pest gaps (grounding-contract-present + ISO-date-in-prompt) and added
  the promptfoo live-model eval via an env-guarded `/eval/grounding` route. Key
  lesson: the `llm-rubric` judge needs the task log embedded in the rubric
  (`{{ tasks | dump }}`) or it false-fails correctly-grounded answers. Eval is
  ad-hoc (real API) — not on every commit. Persistence (#2) and IDOR (#3) from this
  rollout phase remain deferred (no research yet).

## 7. What We Deliberately Don't Test

Exclusions agreed during the rollout (Phase 2 interview, Q5). Future
contributors should respect these unless the underlying assumption changes.

- **Profile management pages** — Breeze scaffolding; Laravel's own test suite covers these. Re-evaluate if custom
  profile logic is added. (Source: interview Q5.)
- **Welcome/landing page** — static HTML with auth links; no logic worth testing. Re-evaluate if dynamic content is
  added. (Source: interview Q5.)
- **Visual snapshot tests** — break on every CSS change, catch nothing meaningful for this project's scale. Use
  functional assertions (element presence, form submission) instead. Re-evaluate if a design system with strict visual
  contracts is adopted. (Source: interview Q5.)

## 8. Freshness Ledger

- Strategy (§1–§5) last reviewed: 2026-06-01
- Stack versions last verified: 2026-06-01
- AI-native tool references last verified: 2026-06-01

Refresh (`/10x-test-plan --refresh`) when:

- a new top-3 risk surfaces from the roadmap or archive,
- a recommended tool's `checked:` date is older than three months,
- the project's tech stack changes (new framework, new test runner),
- §7 negative-space no longer matches what the team believes.
