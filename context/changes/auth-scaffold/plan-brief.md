# Auth Scaffold — Plan Brief

> Full plan: `context/changes/auth-scaffold/plan.md`

## What & Why

Install Laravel Breeze (Blade + Tailwind + Pest) and trim it down to exactly what the PRD asks for: register, log in, log out. This is roadmap item **F-01** — the unblocker for every downstream slice (S-01 task-log-core, S-02 ai-recall-loop, S-03 task-edit-delete), all of which must scope task data to `auth()->user()`.

## Starting Point

Greenfield Laravel 13.8 install with PHP 8.4. The default `users` / `password_reset_tokens` / `sessions` migration is shipped but unmigrated and unused; `User` model has `MustVerifyEmail` interface declared but no verification flow exists. `routes/web.php` has one route returning `welcome.blade.php`. No Breeze, Pest, or auth scaffolding installed. Currently on branch `feat/auth-scaffold`.

## Desired End State

A new visitor can register with name + email + password, log in, see a `/dashboard` placeholder greeting them by name, and log out. The `auth` middleware is wired so future slices can protect routes with `->middleware('auth')`. The Profile flow (Breeze default) gives the user a self-service password-change page — the only safety net since password reset is stripped.

## Key Decisions Made

| Decision                       | Choice                                                                  | Why (1 sentence)                                                                       | Source |
| ------------------------------ | ----------------------------------------------------------------------- | -------------------------------------------------------------------------------------- | ------ |
| Scaffold tool                  | Laravel Breeze (Blade + Tailwind, `--pest`)                             | Roadmap explicitly says "via Breeze"; ships register/login/logout for free            | Plan   |
| Email verification             | Removed (`MustVerifyEmail` stripped)                                    | PRD silent; no mail driver wired; verification would block all registrations          | Plan   |
| Password reset flow            | Removed (routes, views, controllers, migration table)                   | PRD lists only register/login/logout; without mail, reset cannot work anyway          | Plan   |
| Password-confirm middleware    | Removed                                                                  | No sensitive routes need it in MVP                                                     | Plan   |
| Registration fields            | name + email + password + confirmation                                  | Breeze default; `name` already in users table; useful for personalization              | Plan   |
| Password policy                | Laravel default (min 8, confirmed)                                      | Matches convention; appropriate threat model for solo hobby app                        | Plan   |
| Post-login landing             | `/dashboard` placeholder Blade view                                     | Matches Breeze convention; S-01 will extend it; `welcome.blade.php` already links here | Plan   |
| Test framework                 | Pest (via `breeze:install --pest`)                                      | User-selected; clean syntax; project skill suggests future Pest adoption               | Plan   |
| Profile controller             | Kept                                                                    | Provides self-service password change — only safety net once reset is stripped         | Plan   |
| `password_reset_tokens` table  | Edited out of original migration (greenfield, no schema history to preserve) | Cleaner than adding a drop migration on day one                                  | Plan   |

## Scope

**In scope:**

- Install Breeze (Blade + Pest)
- Strip verify-email, password-reset, password-confirm flows
- Remove `MustVerifyEmail` from `User`
- Edit `0001_01_01_000000_create_users_table.php` to drop the `password_reset_tokens` block
- Keep the Profile controller (name/email/password change, account delete)
- Wire `/dashboard` placeholder and confirm welcome-page nav links resolve
- Add one explicit dashboard-gate Pest test

**Out of scope:**

- Email verification
- Password reset
- Password confirmation middleware
- Social auth, 2FA, Sanctum/API tokens
- Real dashboard content (S-01's job)
- Mail driver configuration
- Tailwind config changes

## Architecture / Approach

Three sequential phases with a hard manual gate between Phase 1 and Phase 2:

1. **Phase 1 — Install.** Breeze does most of the work; we verify the full shipped scaffold (including the un-trimmed test suite) runs green under Pest. **Pest must be running before Phase 2.**
2. **Phase 2 — Trim.** Aggressively delete scope we do not need. Edit `routes/auth.php`, delete stripped controllers/views/tests, remove `MustVerifyEmail` from `User`, edit the existing users migration in place to drop `password_reset_tokens`.
3. **Phase 3 — Integrate & verify.** Confirm `welcome.blade.php` links resolve, dashboard placeholder renders, add a dedicated gate test, run full cold-start smoke, open PR.

## Phases at a Glance

| Phase                                          | What it delivers                                                       | Key risk                                                                                  |
| ---------------------------------------------- | ---------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| 1. Install Breeze with Pest                    | Working register/login/logout/dashboard scaffold under a green Pest suite | Breeze's `--pest` flag fails or Pest doesn't pick up `BCRYPT_ROUNDS=4` from phpunit.xml |
| 2. Trim scope                                  | Verify-email, password-reset, password-confirm flows fully removed     | Deleting too aggressively and breaking the kept Profile flow                              |
| 3. Wire to welcome page + final verification   | Dashboard placeholder live, smoke flow validated, PR opened             | Breeze overwriting `welcome.blade.php` and losing the GardenLog content                   |

**Prerequisites:** Currently on `feat/auth-scaffold` branch; clean working tree; SQLite dev DB.
**Estimated effort:** 1 session, ~2-3 hours across 3 phases.

## Open Risks & Assumptions

- Breeze's `--pest` flag works as expected on Laravel 13.8 / Breeze 2.x. If it does not, Phase 1 stalls until we install Pest manually and convert tests — surfaced explicitly by the hard gate.
- Editing the original users migration is acceptable because no environment has migrated it yet (greenfield). If anyone has run `php artisan migrate` on a sibling worktree, this assumption breaks — verify before Phase 2.
- Branch protection on `master` is configured (per lessons rule). PR-and-CI workflow is the only path to merge.

## Success Criteria (Summary)

- A new visitor can register, log in, see `/dashboard` greeting them, and log out — fully end-to-end via the browser.
- `php artisan route:list` shows only `register`, `login`, `logout`, `dashboard`, `profile.*` — no `password.*` or `verification.*`.
- `composer test` (Pest) and `./vendor/bin/pint --test` both exit 0 in a clean clone.
