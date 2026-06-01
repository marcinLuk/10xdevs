---
project: GardenLog
version: 7
status: draft
created: 2026-05-25
updated: 2026-06-01
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

| ID   | Change ID        | Outcome (user can …)                                                         | Prerequisites | PRD refs               | Status   | Wave |
|------|------------------|------------------------------------------------------------------------------|---------------|------------------------|----------|------|
| F-01 | auth-scaffold    | (foundation) register, log in, and log out                                   | —             | FR-001, FR-002, FR-003 | done     | —    |
| S-01 | task-log-core    | add a task and view it in the chronological list                             | F-01          | FR-004, FR-005, FR-006 | done     | —    |
| S-02 | ai-recall-loop   | ask the AI about their task history and get a grounded, date-specific answer | S-01          | FR-009, FR-010, US-01  | done     | —    |
| S-03 | task-edit-delete | edit or delete a saved task                                                  | S-01          | FR-007, FR-008         | done     | —    |
| S-05 | branding-nav     | see a GardenLog logo instead of the Laravel logo; green theme across all UI  | F-01          | UX polish              | done     | —    |
| S-06 | welcome-page     | land on a clean, light welcome page with a branded card and auth actions     | F-01          | UX polish              | done     | —    |
| S-04 | ai-search-ux     | get inline validation feedback and UX polish for AI searchbar                | S-02, S-03    | UX polish              | done     | —    |
| S-07 | ui-fixes-round-1 | see PNG logo everywhere, fixed welcome layout, and working submit buttons   | S-04          | UX polish              | ready    | —    |

> All PRD slices completed. S-07 is the remaining UI bugfix/polish item.

## Streams

Navigation aid — groups items that share a Prerequisites chain. Canonical ordering still lives in the dependency graph
below; this table is the proposed reading order across parallel tracks.

| Stream | Theme              | Chain                              | Note                                                                                                             |
|--------|--------------------|-------------------------------------|------------------------------------------------------------------------------------------------------------------|
| A      | Auth & recall path | `F-01` → `S-01` → `S-02` → `S-04` | The must-have path through all PRD features + UX polish. S-04 also waits for S-03 (file conflict).              |
| B      | Task CRUD          | `S-01` → `S-03`                    | Branches from `S-01`; parallel with S-05/S-06 in Wave 1.                                                        |
| C      | UX polish          | `F-01` → `S-05`, `S-06`            | Independent of Streams A/B. Both run in Wave 1; S-05 replaces logo that S-06 consumes (soft dep, no conflict).   |

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
- **Unknowns:** —
- **Risk:** Resolved — context injection approach validated in implementation. Prompt hardened against injection (F2, F3).
- **Status:** done

### S-03: Task editing and deletion

- **Outcome:** user can edit the description or type tag of a saved task, or delete a task entirely
- **Change ID:** task-edit-delete
- **PRD refs:** FR-007, FR-008
- **Prerequisites:** S-01
- **Parallel with:** S-05, S-06 (Wave 1)
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Standard CRUD operations on an existing entity. 3-phase plan (backend → frontend → tests). Modifies
  `TaskController.php` — creates a file conflict with S-04, so S-04 must merge after this.
- **Plan:** `context/changes/task-edit-delete/plan.md`
- **Status:** done

### S-05: Branding & navigation cleanup

- **Outcome:** the Laravel logo is replaced with a GardenLog leaf icon + "GardenLog" text mark across all views
  (navigation bar, auth pages); all interactive elements use a green/earth-tone color palette instead of indigo
- **Change ID:** branding-nav
- **PRD refs:** UX polish (no new FR)
- **Prerequisites:** F-01
- **Parallel with:** S-03, S-06 (Wave 1)
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Cosmetic-only change; touches 7 Blade component files + `.env`. No logic, no routes, no data changes.
  Zero file overlap with S-03 or S-06. S-06 consumes `<x-application-logo>` which this slice replaces (soft dep —
  both work regardless of merge order, but visually best if S-05 merges first or same time as S-06).
- **Plan:** `context/changes/branding-nav/plan.md`
- **Status:** done

### S-06: Welcome page redesign

- **Outcome:** the default Laravel welcome page is replaced with a light-themed GardenLog landing page;
  the page has a white background, a single centred card containing the logo, a welcome headline, and
  Register / Log in buttons; all Laravel marketing copy is removed
- **Change ID:** welcome-page
- **PRD refs:** UX polish (no new FR)
- **Prerequisites:** F-01
- **Parallel with:** S-03, S-05 (Wave 1)
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Replaces `resources/views/welcome.blade.php` entirely; no backend changes. Only touches one file —
  zero conflict with any other slice. Consumes `<x-application-logo>` which S-05 replaces (soft dep — works
  with either old or new logo).
- **Plan:** `context/changes/welcome-page/plan.md`
- **Status:** done

### S-04: AI search UX polish

- **Outcome:** helper text hint for disabled Ask button, question echo above AI answer, contextual hint when no tasks
  exist. Small UX affordances discovered during S-02 manual testing.
- **Change ID:** ai-search-ux
- **PRD refs:** UX polish (no new FR; refines FR-009 surface)
- **Prerequisites:** S-02 (done), S-03 (done)
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Small change (1 Blade partial + 1 controller line + tests). All prerequisites merged; ready to implement.
- **Plan:** `context/changes/ai-search-ux/plan.md`
- **Status:** done

### S-07: UI fixes round 1

- **Outcome:** user sees the PNG logo (not SVG) on welcome page, nav bar, and guest layout; welcome page has tighter
  container width and proper padding; submit buttons are visible and functional on login, register, searchbar, and
  task add/edit forms
- **Change ID:** ui-fixes-round-1
- **PRD refs:** UX polish (no new FR; fixes regressions from S-05/S-06 and restores missing buttons)
- **Prerequisites:** S-04 (done)
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Cosmetic + regression fixes only. Touches Blade components and views; no backend logic changes. Logo PNG
  already exists in repo. Bugs #3–#5 are regressions from recent UI changes — straightforward restore.
- **Status:** ready

## Backlog Handoff

| Roadmap ID | Change ID        | Suggested issue title                                     | Ready for `/10x-implement` | Notes                                        |
|------------|------------------|-----------------------------------------------------------|----------------------------|----------------------------------------------|
| F-01       | auth-scaffold    | Auth scaffold: register, login, logout via Laravel Breeze | done                       | Merged — PR #15                              |
| S-01       | task-log-core    | Task log: add task + chronological list view              | done                       | Implemented — PR #16                         |
| S-02       | ai-recall-loop   | AI recall: natural-language query → grounded answer       | done                       | Implemented — PR #18                         |
| S-03       | task-edit-delete | Task CRUD: edit and delete saved tasks                    | done                       | Implemented — PR #20                         |
| S-05       | branding-nav     | Branding: leaf logo + green theme across all UI           | done                       | Implemented — PR #21                         |
| S-06       | welcome-page     | Welcome page: light theme, branded card, auth in card     | done                       | Implemented — PR #22                         |
| S-04       | ai-search-ux     | AI search UX: hints, question echo, empty-tasks message   | done                       | Implemented — PR #23                          |
| S-07       | ui-fixes-round-1 | UI fixes: PNG logo, welcome layout, restore submit buttons| yes                        | All prerequisites merged — ready to implement |

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

- **F-01: Auth scaffold** — Archived — `context/changes/auth-scaffold/`. PR #15. Lesson: —.
- **S-01: Task log core** — Implemented — `context/changes/task-log-core/`. PR #16. Lesson: —.
- **S-02: AI recall loop** — Implemented — `context/changes/ai-recall-loop/`. PR #18. Lesson: prompt hardened against
  injection (F2, F3); Prism::fake used for testing.
- **S-03: Task editing and deletion** — Implemented — `context/changes/task-edit-delete/`. PR #20. Lesson: —.
- **S-05: Branding & navigation cleanup** — Implemented — `context/changes/branding-nav/`. PR #21. Lesson: —.
- **S-06: Welcome page redesign** — Implemented — `context/changes/welcome-page/`. PR #22. Lesson: —.
- **S-04: AI search UX polish** — Implemented — `context/changes/ai-search-ux/`. PR #23. Lesson: —.
