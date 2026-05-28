<!-- IMPL-REVIEW-REPORT -->

# Implementation Review: AI Recall Loop

- **Plan**: context/changes/ai-recall-loop/plan.md
- **Scope**: Full plan (Phases 1–4)
- **Date**: 2026-05-28
- **Verdict**: NEEDS ATTENTION
- **Findings**: 0 critical | 4 warnings | 2 observations

## Verdicts

| Dimension           | Verdict |
|---------------------|---------|
| Plan Adherence      | PASS    |
| Scope Discipline    | WARNING |
| Safety & Quality    | WARNING |
| Architecture        | PASS    |
| Pattern Consistency | PASS    |
| Success Criteria    | PASS    |

## Evidence

- All 13 planned files present and implement intended contract.
- Automated checks passed: `php artisan route:list` shows `POST tasks/ask` with `auth` middleware;
  `./vendor/bin/pint --test` passes; `php artisan test` → 50/50 tests, 123 assertions.
- Two unplanned-but-supportive files: `database/factories/TaskFactory.php`, `database/seeders/DatabaseSeeder.php`.
- Service hardened beyond plan with `withClientOptions(timeout: 10)` and `withClientRetry(2, 100)` — see F1.

## Findings

### F1 — Retry budget can exceed the 5-second NFR

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Safety & Quality
- **Location**: app/Services/AiRecallService.php:30-31
- **Detail**: `withClientOptions(['timeout' => 10])` + `withClientRetry(2, 100)` permits ~30s worst-case wall time (3
  attempts × 10s). The plan's 5-second NFR is silently violated on transient failures.
- **Fix**: Reduce to `withClientRetry(1, 100)` and lower timeout to 5–7s so worst case stays close to the 5s NFR.
- **Decision**: PENDING

### F2 — Prompt injection: user question and task text unguarded

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Services/AiRecallService.php:29, resources/views/prompts/garden-recall.blade.php:14
- **Detail**: Both user question and task descriptions are interpolated raw. Self-tenant attack only, but cheap to
  harden.
- **Fix**: Wrap user-controlled text in delimiters (e.g. `<user_question>…</user_question>`,
  `<task_entry>…</task_entry>`) and instruct the model to treat delimited content as data.
- **Decision**: PENDING

### F3 — Blade `{{ }}` HTML-encodes descriptions in a non-HTML prompt

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: resources/views/prompts/garden-recall.blade.php:14
- **Detail**: The prompt is plain text sent to the LLM, not HTML. Blade's `e()` turns `&` into `&amp;`, etc., degrading
  grounding fidelity.
- **Fix**: Use `{!! $task->description !!}` (and `{!! $task->type !!}`) paired with the F2 delimiter wrapping.
- **Decision**: PENDING

### F4 — DatabaseSeeder hardcodes weak password without env guard

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Scope Discipline / Safety & Quality
- **Location**: database/seeders/DatabaseSeeder.php:24
- **Detail**: Unplanned seeder change creates `test@example.com` with password `'test'` and 20 tasks. No
  `app()->environment()` guard — `db:seed` in any environment writes these credentials.
- **Fix**: Add `if (! app()->environment(['local', 'testing'])) return;` to the seeder, or remove the change and track
  as follow-up.
- **Decision**: PENDING

### F5 — Model slug differs from plan: `claude-sonnet-4.5` vs `claude-sonnet-4-5`

- **Severity**: ℹ️ OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Adherence
- **Location**: .env.example:68-74, app/Services/AiRecallService.php:16
- **Detail**: Implementation uses `anthropic/claude-sonnet-4.5` (dot); plan wrote `claude-sonnet-4-5` (dash). OpenRouter
  canonical slug uses the dot — plan typo.
- **Fix**: Update plan.md:78 to reference `claude-sonnet-4.5`.
- **Decision**: PENDING

### F6 — TaskFactory + DatabaseSeeder added as unplanned test/dev infra

- **Severity**: ℹ️ OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Scope Discipline
- **Location**: database/factories/TaskFactory.php, database/seeders/DatabaseSeeder.php
- **Detail**: Not in plan's "Changes Required" but needed by Phase 4 tests. Factory is standard; seeder is the risk
  surface (see F4).
- **Fix**: Document as an addendum in plan.md's Phase 4 file list.
- **Decision**: PENDING
