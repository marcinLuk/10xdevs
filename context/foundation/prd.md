---
project: "GardenLog"
version: 1
status: draft
created: 2026-05-23
context_type: greenfield
product_type: web-app
target_scale:
  users: medium
  qps: low
  data_volume: small
timeline_budget:
  mvp_weeks: 3
  hard_deadline: null
  after_hours_only: true
---

## Vision & Problem Statement

Home gardeners track tasks — watering, fertilizing, planting — in calendars or notes, but retrieving specific information requires manual scanning. When a gardener needs to answer "when did I last fertilize my tomatoes?", they must hunt through past entries, which is slow and frustrating.

The insight: existing gardening apps are feature-bloated and complex. A radically simple task log with AI-powered natural-language query lets the gardener ask their own task history directly — zero browsing required.

## User & Persona

**Primary persona**: A home or hobby gardener managing their own garden. They already track tasks (or wish they did), but retrieving historical data is painful. They are not a power-user of productivity apps; they want minimal setup and immediate answers to questions about their own garden.

## Success Criteria

### Primary
- A gardener can open the app, add a task to their task list (with a date, description, and optional type tag), then ask the AI "when did I fertilize my tomatoes?" and receive a correct, date-specific answer drawn from their own task history.

### Secondary
- Tasks display a colour or icon by type (watering, fertilizing, planting) so entries are scannable at a glance without opening each one.

### Guardrails
- Task data must never be silently lost — a saved task must appear in the task list and persist across sessions.
- The AI must never return a date or event not present in the gardener's task history; when no matching task is found it must say so explicitly rather than invent an answer.

## User Stories

### US-01: Gardener asks AI about a past task

- **Given** a logged-in gardener who has saved at least one task (e.g. "fertilized tomatoes" on a specific date)
- **When** they type "when did I fertilize my tomatoes?" into the search bar
- **Then** the AI responds with the date of that task, drawn from their own task history

#### Acceptance Criteria
- The AI response references the actual date saved in the gardener's task list
- If no matching task exists, the AI responds with "I don't see that task in your history" (or equivalent) — never an invented date
- The response is visible within 5 seconds of submitting the question

## Functional Requirements

### Authentication
- FR-001: Gardener can register with email and password. Priority: must-have
  > Socrates: No counter-argument; stands as written.

- FR-002: Gardener can log in with email and password. Priority: must-have
  > Socrates: No counter-argument; stands as written.

- FR-003: Gardener can log out. Priority: must-have
  > Socrates: No counter-argument; stands as written.

### Task List (MVP default view)
- FR-004: Gardener can view their tasks in a chronological task list. Priority: must-have
  > Socrates: Counter-argument considered: "A list view might be simpler to build and equally useful for AI recall." Resolution: week-view downgraded to nice-to-have; chronological list view is the MVP default.

- FR-005: Gardener can scroll through their task history in the list view. Priority: must-have
  > Socrates: Revised from "navigate between weeks" — week navigation now tied to the nice-to-have week-view FR (FR-012).

### Task Management
- FR-006: Gardener can add a task with a free-text description (required) and an optional type tag (watering, fertilizing, planting, or custom free-text). Priority: must-have
  > Socrates: Counter-argument considered: "Fixed taxonomy may not cover real gardening tasks — gardeners will hit 'custom' constantly." Resolution: type tag made optional; free-text description is always sufficient on its own.

- FR-007: Gardener can edit a saved task's description or type tag. Priority: must-have
  > Socrates: No counter-argument; stands as written.

- FR-008: Gardener can delete a saved task. Priority: must-have
  > Socrates: No counter-argument; stands as written.

### AI Search
- FR-009: Gardener can ask a natural-language question about their task history via a search bar. Priority: must-have
  > Socrates: No counter-argument; stands as written.

- FR-010: Gardener receives an AI answer drawn exclusively from their own task history; when no matching task is found the AI says so explicitly rather than inventing an answer. Priority: must-have
  > Socrates: No counter-argument; stands as written.

### Display (nice-to-have)
- FR-011: Task entries show a colour or icon by type tag when one is present; tasks without a type tag display as grey/blank. Priority: nice-to-have
  > Socrates: Counter-argument considered: "Optional tags make colour-coding inconsistent." Resolution: kept; partial colour-coding when tag exists, grey/blank otherwise — acceptable visual state.

- FR-012: Gardener can view their tasks on a week-view calendar. Priority: nice-to-have
  > Socrates: Downgraded from must-have (FR-004) — week-view is product aspiration, not MVP requirement. See FR-004 resolution.

## Non-Functional Requirements

- The AI response is visible within 5 seconds of the gardener submitting a question.
- The AI never returns a date or event not present in the gardener's task history; when no matching task is found it says so explicitly.
- A task saved in one session is available in all subsequent sessions on any device the gardener is logged into.
- The app is usable on current versions of mainstream desktop browsers; mobile browser support is a nice-to-have, not an MVP requirement.
- The gardener's task data is used only to answer their own queries — it is not shared or used beyond that scope.

## Business Logic

The app answers natural-language questions about a gardener's own task history by matching the question against their saved tasks and returning grounded, date-specific responses.

The input is a free-text question the gardener types (e.g. "when did I last water my roses?"). The app searches the gardener's task history for entries that match the subject and action in the question. The output is a direct answer referencing the actual date(s) found — or an explicit "I don't see that task" when no match exists. The gardener encounters this as a conversational exchange in the search bar, not a filter or a list of results.

## Access Control

Single user; email and password login; no roles. The gardener creates one account and all their garden task data lives behind that login. No sharing, no teams, no guest access for MVP. The gardener can access their data from any device they log into.

## Non-Goals

- **No AI gardening advice**: the AI answers recall questions only ("when did I…?"); it never suggests what the gardener should do next. Proactive recommendations are out of scope for the MVP.
- **No recurring or scheduled tasks**: every task is added manually. Recurrence rules add complexity without proving the core AI-recall loop.
- **No sharing or multi-user collaboration**: single gardener, single account. No shared plots, no household access, no team features.
- **No native mobile app**: desktop browser is the MVP target. Mobile browser support is a nice-to-have; native iOS/Android apps are out of scope.
- **No push notifications or reminders**: the app is pull-only — the gardener comes to the app. It does not proactively alert or remind.

## Open Questions

No unresolved questions — all required elements were captured and accepted during the shaping session (`quality_check_status: accepted`). If new questions arise during tech-stack selection or implementation planning, add them here as numbered entries.
