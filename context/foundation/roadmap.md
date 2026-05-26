---
project: GardenLog
version: 3
status: draft
created: 2026-05-25
updated: 2026-05-26
prd_version: 1
main_goal: speed
top_blocker: capacity
---

# Roadmap: GardenLog

> Derived from `context/foundation/prd.md` (v1) + auto-researched codebase baseline.
> Edit-in-place; archive when superseded.
> Slices below are listed in dependency order. The "At a glance" table is the index.

## Vision recap

Home gardeners track tasks — watering, fertilizing, planting — but finding past records requires manual scanning through
calendars or notes. GardenLog bets on radical simplicity: a minimal task log paired with a natural-language AI search
bar so gardeners can ask "when did I last fertilize my tomatoes?" and get an instant, date-specific answer drawn from
their own history. Success means the AI recall loop works reliably — no invented dates, no lost tasks.

## North star

**S-02: AI recall loop — user can ask a natural-language question and get a grounded, date-specific answer.**

> The north star — the smallest end-to-end slice whose successful delivery proves the core product hypothesis, the idea
> that natural-language recall of a user's own garden history is both accurate and immediately useful — is placed as
> early as Prerequisites allow because every other slice only matters if this works. Concretely: a logged-in gardener
> adds a task, asks the AI about it, and receives an answer that references only data they saved.

## At a glance

| ID   | Change ID        | Outcome (user can …)                                                         | Prerequisites | PRD refs               | Status   |
|------|------------------|------------------------------------------------------------------------------|---------------|------------------------|----------|
| F-01 | auth-scaffold    | (foundation) register, log in, and log out                                   | —             | FR-001, FR-002, FR-003 | done     |
| S-01 | task-log-core    | add a task and view it in the chronological list                             | F-01          | FR-004, FR-005, FR-006 | done     |
| S-02 | ai-recall-loop   | ask the AI about their task history and get a grounded, date-specific answer | S-01          | FR-009, FR-010, US-01  | ready    |
| S-03 | task-edit-delete | edit or delete a saved task                                                  | S-01          | FR-007, FR-008         | ready    |

## Streams

Navigation aid — groups items that share a Prerequisites chain. Canonical ordering still lives in the dependency graph
below; this table is the proposed reading order across parallel tracks.

| Stream | Theme              | Chain                     | Note                                                                                                             |
|--------|--------------------|---------------------------|------------------------------------------------------------------------------------------------------------------|
| A      | Auth & recall path | `F-01` → `S-01` → `S-02`  | The must-have path — the sequence through all PRD must-have features — biased for `main_goal: speed`; validates the north star. |
| B      | Task CRUD          | `S-01` → `S-03`           | Branches from `S-01`; parallel with `S-02` — maximises throughput given `capacity` is the top blocker.          |

## Baseline

What's already in place in the codebase as of 2026-05-26 (auto-researched + user-confirmed).
Foundations below assume these are present and do NOT re-scaffold them.

- **Frontend:** Present — Vite + Tailwind CSS v3 + Alpine.js wired; `resources/views/dashboard.blade.php` (custom);
  Breeze auth/profile views in place
- **Backend / API:** Present — auth + profile + task routes (`routes/web.php`, `routes/auth.php`); `TaskController` with
  index and store actions; no `api.php`
- **Data:** Present (domain) — `tasks` table with migration, Task model, TaskFactory; user-scoped with composite index
- **Auth:** Present — Laravel Breeze fully installed and wired; auth routes + middleware active; dashboard and profile
  routes gated behind `auth` middleware
- **Deploy / infra:** Partial — Docker (`docker-compose.yml` + `Dockerfile.local`) and Railway (`railway.json`)
  configured; GitHub Actions absent
- **Observability:** Absent — Laravel default logging only; `/up` health endpoint present (`bootstrap/app.php:11`);
  no Sentry/Datadog/APM

## Foundations

### F-01: Auth scaffold

- **Outcome:** (foundation) register, log in, and log out functionality is in place; routes, controllers, and middleware
  are wired via Laravel Breeze; all subsequent routes can use the `auth` middleware
- **Change ID:** auth-scaffold
- **PRD refs:** FR-001, FR-002, FR-003, Access Control section
- **Unlocks:** S-01, S-02, S-03 — all user-scoped slices require the `auth` middleware and the users table to be in
  place before they can scope task data to the authenticated user
- **Prerequisites:** —
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:** —
- **Risk:** No technical unknowns; Laravel Breeze is the standard scaffold. Sequenced first because every subsequent
  slice scopes data to the authenticated user — without it, task ownership cannot be enforced.
- **Status:** done

## Slices

### S-01: Task log core

- **Outcome:** user can add a task with a date, free-text description, and optional type tag (watering, fertilizing,
  planting, or custom), then see all their tasks in a scrollable chronological list
- **Change ID:** task-log-core
- **PRD refs:** FR-004, FR-005, FR-006
- **Prerequisites:** F-01
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Standard CRUD on a new `tasks` table. Sequenced immediately after auth so task data is scoped to the user
  and available for the AI recall slice. Skipping this means S-02 has no real data to query against.
- **Status:** done

### S-02: AI recall loop

- **Outcome:** user can type a natural-language question ("when did I fertilize my tomatoes?") into a search bar and
  receive a grounded, date-specific answer drawn exclusively from their own task history; if no matching task exists the
  AI says so explicitly rather than inventing an answer
- **Change ID:** ai-recall-loop
- **PRD refs:** FR-009, FR-010, US-01
- **Prerequisites:** S-01
- **Parallel with:** S-03
- **Blockers:** —
- **Unknowns:**
  - How should the AI provider prompt be structured to ensure responses are drawn exclusively from the user's task
    history and never invent dates or events? — Owner: developer. Block: no (context injection — passing task history
    as context to the AI provider — is a well-understood integration pattern; can be decided at plan time).
- **Risk:** The grounding NFRs ("AI never returns data not in history", "response within 5 seconds") are the riskiest
  acceptance criteria in the PRD. Sequenced after S-01 so real task data is available for end-to-end testing; the
  Unknown is low-risk because context injection is a well-understood pattern.
- **Status:** ready

### S-03: Task editing and deletion

- **Outcome:** user can edit the description or type tag of a saved task, or delete a task entirely
- **Change ID:** task-edit-delete
- **PRD refs:** FR-007, FR-008
- **Prerequisites:** S-01
- **Parallel with:** S-02
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Standard CRUD operations on an existing entity. Marked parallel with S-02 to maximise throughput given
  capacity is the top blocker; an AI agent can work on this while another handles S-02.
- **Status:** ready

## Backlog Handoff

| Roadmap ID | Change ID        | Suggested issue title                                     | Ready for `/10x-plan` | Notes                               |
|------------|------------------|-----------------------------------------------------------|-----------------------|-------------------------------------|
| F-01       | auth-scaffold    | Auth scaffold: register, login, logout via Laravel Breeze | done                  | Merged — no planning needed         |
| S-01       | task-log-core    | Task log: add task + chronological list view              | done                  | Implemented — PR #16                |
| S-02       | ai-recall-loop   | AI recall: natural-language query → grounded answer       | yes                   | Run `/10x-plan ai-recall-loop`      |
| S-03       | task-edit-delete | Task CRUD: edit and delete saved tasks                    | yes                   | Run `/10x-plan task-edit-delete`; parallel with S-02 |

## Open Roadmap Questions

None. PRD carried no unresolved open questions at time of generation.

## Parked

- **FR-011: Colour/icon display by task type** — Why parked: nice-to-have per PRD §Display. Deferred to keep the
  must-have path lean (main goal: speed).
- **FR-012: Week-view calendar** — Why parked: downgraded from must-have in PRD §FR-004 resolution; nice-to-have per
  PRD. Deferred.
- **GitHub Actions CI/CD pipeline** — Why parked: tech-stack.md declares `ci_provider: github-actions` but no workflows
  exist. Not on the must-have path; Railway deploy is already configured and sufficient for MVP.
- **Observability (error tracking / APM)** — Why parked: no PRD NFR requires Sentry or APM for MVP launch. The `/up`
  health endpoint is sufficient. Deferred.

## Done

- **F-01: Auth scaffold** — Archived — `context/changes/auth-scaffold/`. Lesson: —.
- **S-01: Task log core** — Implemented — `context/changes/task-log-core/`. PR #16. Lesson: —.
