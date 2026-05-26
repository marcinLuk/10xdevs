# Auth Scaffold Implementation Plan

## Overview

Stand up the auth foundation (F-01) for GardenLog by installing Laravel Breeze (Blade + Tailwind + Pest), then trimming the scaffold to exactly what the PRD calls for: register, log in, log out, plus a `/dashboard` landing route protected by the `auth` middleware. Strip Breeze's verify-email, password-reset, and password-confirmation flows — they are out of scope per the PRD and require mail infrastructure we do not have. Keep the Profile controller so the user retains a self-service password-change path (the only safety net for forgotten passwords once reset is removed).

## Current State Analysis

- `app/Models/User.php:15` declares `User implements MustVerifyEmail`, casts password as `hashed`, and uses PHP-8-attribute `#[Fillable(['name','email','password'])]`. The `MustVerifyEmail` interface is unused — there is no verification flow wired — and we will remove it in Phase 2.
- `database/migrations/0001_01_01_000000_create_users_table.php:14-37` creates three tables: `users`, `password_reset_tokens`, `sessions`. The `password_reset_tokens` block (lines 24-28) becomes dead schema once reset is stripped.
- `routes/web.php:5-7` defines only `GET /` returning `welcome.blade.php`. No `auth` middleware groups, no `dashboard`, no named `login`/`register` routes yet — but `resources/views/welcome.blade.php:22-30` already conditionally renders Dashboard/Login links via `Route::has('login')` and `@auth`, so it expects those named routes to exist post-Breeze.
- `composer.json` requires `php: ^8.4`, `laravel/framework: ^13.8`, `phpunit/phpunit: ^12.5.12`. No Breeze, no Pest, no Fortify, no Sanctum. `composer test` runs `artisan test`.
- `phpunit.xml:26-27` configures SQLite `:memory:` for tests with `BCRYPT_ROUNDS=4`. Pest reuses this PHPUnit config.
- Current branch: `feat/auth-scaffold`. Lessons file mandates branch + PR (no direct master commits).

## Desired End State

After this plan ships, a brand-new visitor can:

1. Visit `/`, see a header with a **Log in** / **Register** link.
2. Click Register → fill `name + email + password + password_confirmation` → land on `/dashboard`.
3. Click **Log out** → return to `/`.
4. Re-visit `/login` → enter credentials → land on `/dashboard`.
5. Visit `/dashboard` while unauthenticated → be redirected to `/login`.
6. Edit profile name/email or change password at `/profile` (Breeze default).

Verification:
- `composer test` runs Pest and exits 0.
- `php artisan route:list --except-vendor` shows `register`, `login`, `logout`, `dashboard`, `profile.*` — **no** `password.email`, `password.update`, `verification.*`, `password.confirm` routes.
- `./vendor/bin/pint --test` is clean.

### Key Discoveries

- Breeze 2.x supports `breeze:install blade --pest` to scaffold a Pest test suite directly; this avoids a separate Pest-conversion step.
- The default Breeze Blade install ships with **Alpine.js** (small JS dep); this is acceptable for the tech-stack and does not conflict with Tailwind v4.
- Breeze writes a separate `routes/auth.php` file that is `require`d from `routes/web.php`; Phase 2 trimming should edit `routes/auth.php` (not `routes/web.php`).
- The `email_verified_at` column on `users` is harmless once `MustVerifyEmail` is removed — we keep the column to avoid migration churn and to leave room for future verification.

## What We're NOT Doing

- **No email verification.** PRD is silent; no mail driver wired; `MustVerifyEmail` interface removed.
- **No password reset (`forgot-password` / `reset-password`).** Stripped entirely — controllers, views, routes, and the `password_reset_tokens` migration block. Users who forget passwords are locked out until we add it back.
- **No "confirm password before sensitive action" flow.** Stripped — we have no sensitive routes that need it.
- **No remember-me UX changes.** Breeze ships a "Remember me" checkbox; we keep it untouched.
- **No social auth, no 2FA, no Sanctum, no API tokens.** Out of scope per PRD.
- **No real dashboard content.** `/dashboard` is a placeholder `"Hello, {{ Auth::user()->name }}"` view; S-01 (task-log-core) replaces it.
- **No Tailwind config changes.** Breeze's Blade install respects the existing Tailwind v4 setup in `resources/css/app.css`.

## Implementation Approach

Breeze does most of the work; the value-add of this plan is the **trim**. We install with Pest (Phase 1), prove the scaffold and tests run green, then aggressively delete what the PRD doesn't ask for (Phase 2), then verify the integration with the existing welcome page and the user-confirmable end-to-end flow (Phase 3).

The hard manual gate is between Phase 1 and Phase 2: **Pest must be running green** with the full Breeze-shipped test suite before we start deleting anything. If `--pest` fails or Pest doesn't pick up the BCRYPT_ROUNDS=4 env from `phpunit.xml`, we resolve that before touching scope-trim work.

## Critical Implementation Details

- **Pest binding to `phpunit.xml` env.** Pest reads `phpunit.xml` for `<php><env>` overrides — confirm `BCRYPT_ROUNDS=4` is in effect during Pest runs, otherwise auth tests are 10x slower and may time out on CI.
- **Migration edit, not new migration.** The `password_reset_tokens` table is part of the original-and-unmigrated `0001_01_01_000000_create_users_table.php`. Since no environment has migrated yet (greenfield), edit the original migration's `up()` and `down()` rather than writing a new drop migration. This keeps the schema history clean.
- **Order of trim.** Remove route entries from `routes/auth.php` **before** deleting the controllers, otherwise `route:list` blows up mid-edit and obscures other failures.

## Phase 1: Install Breeze with Pest

### Overview

Install Laravel Breeze (Blade flavor) with Pest tests, run the shipped migrations, and confirm both the dev server and the Pest test suite are green before any trimming. This phase is a **hard gate**: do not proceed to Phase 2 until Pest is verifiably running.

### Changes Required:

#### 1. Composer dependencies

**File**: `composer.json` (and `composer.lock`)

**Intent**: Add Breeze as a dev dependency. Pest will be added by the Breeze installer's `--pest` flag.

**Contract**: `require-dev` gains `laravel/breeze: ^2.x`. After `breeze:install blade --pest` runs, `require-dev` also gains `pestphp/pest`, `pestphp/pest-plugin-laravel`, and possibly `pestphp/pest-plugin-arch`. The `phpunit/phpunit ^12.5.12` requirement stays (Pest depends on it).

#### 2. Breeze scaffold install

**File**: project tree (generated)

**Intent**: Run `php artisan breeze:install blade --pest` to scaffold register/login/logout/forgot-password/reset-password/verify-email/confirm-password controllers, requests, views, routes, and Pest test suite. Accept Breeze's defaults (no dark mode flag, no SSR — this is a Blade-only install).

**Contract**: After install, the following exist and `composer test` exits 0:
- `app/Http/Controllers/Auth/*.php` (≈8 controllers from Breeze)
- `app/Http/Requests/Auth/LoginRequest.php`
- `routes/auth.php` (required from `routes/web.php`)
- `resources/views/auth/*.blade.php` and `resources/views/layouts/{app,guest,navigation}.blade.php`
- `resources/views/dashboard.blade.php`, `resources/views/profile/*`
- `tests/Feature/Auth/*.php` and `tests/Pest.php` (Pest bootstrap)
- `tests/Feature/ProfileTest.php`
- `package.json` gains Alpine.js; `npm install && npm run build` runs cleanly

#### 3. Run migrations

**File**: SQLite dev database

**Intent**: Apply the default users/cache/jobs migrations so the auth routes can actually register users.

**Contract**: `php artisan migrate` succeeds; `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` tables exist.

### Success Criteria:

#### Automated Verification:

- Composer install completes: `composer install` exits 0
- Breeze install completes: `php artisan breeze:install blade --pest` exits 0
- Migrations apply cleanly: `php artisan migrate` exits 0
- Frontend builds: `npm install && npm run build` exit 0
- **Pest suite passes (hard gate): `composer test` exits 0 and the output banner reads "Pest" (not "PHPUnit")**
- Lint clean: `./vendor/bin/pint --test` exits 0
- Routes present: `php artisan route:list` includes `register`, `login`, `logout`, `dashboard`
- Auth routes live in `routes/auth.php`: the file exists AND `routes/web.php` contains a `require __DIR__.'/auth.php';` (or equivalent `include`) so Phase 2 can edit auth route definitions in one place

#### Manual Verification:

- Dev server starts: `composer dev` boots without errors
- Browser smoke test: `/register` renders the register form; `/login` renders the login form; submitting valid credentials lands on `/dashboard`; clicking "Log out" returns to `/`
- **Pest is the test runner**: the `composer test` output shows the Pest ASCII banner and uses Pest's `it()/test()` syntax in `tests/Feature/Auth/`

**Implementation Note**: After this phase pause for explicit human confirmation that Pest is the active test runner and `composer test` is green. **Phase 2 must not start until this gate passes.**

---

## Phase 2: Trim scope (remove verify, reset, confirm)

### Overview

Delete every Breeze artifact that supports a feature the PRD does not require: email verification, password reset, and password confirmation. Remove the `MustVerifyEmail` interface from `User`. Drop the `password_reset_tokens` table from the original users migration. Update the Pest test suite to delete tests for stripped features. Keep registration, login, logout, dashboard, and profile.

### Changes Required:

#### 1. User model

**File**: `app/Models/User.php`

**Intent**: Remove the `MustVerifyEmail` interface and its `use` import since we no longer require verification. Leave fillable, hidden, factory, notifiable, and the `casts()` method untouched.

**Contract**: Class signature becomes `class User extends Authenticatable` (no `implements MustVerifyEmail`). The `Illuminate\Contracts\Auth\MustVerifyEmail` import is removed. `email_verified_at` casting in `casts()` stays — column remains, just unused.

#### 2. Auth routes

**File**: `routes/auth.php`

**Intent**: Remove route definitions for password reset (`password.request`, `password.email`, `password.reset`, `password.store`), email verification (`verification.notice`, `verification.verify`, `verification.send`), and password confirmation (`password.confirm`, `password.update`). Keep register, login, logout.

**Contract**: After edit, `php artisan route:list` shows only: `register` (GET+POST), `login` (GET+POST), `logout` (POST). The `password.*` and `verification.*` routes are absent. The `auth` middleware group still wraps `logout`.

#### 3. Delete stripped controllers

**File**: delete the following files

**Intent**: Remove the Breeze controllers backing the stripped routes.

**Contract**: These files no longer exist:
- `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `app/Http/Controllers/Auth/VerifyEmailController.php`

**Explicitly KEEP**:
- `app/Http/Controllers/Auth/PasswordController.php` — this is the **Profile password-update controller** wired from `routes/web.php` in the standard Breeze 2.x Blade install. It is unrelated to the password-reset flow (which lives in `NewPasswordController` + `PasswordResetLinkController`).

**Cross-check** (fail-closed if Breeze diverges from the assumption): before deleting, run `grep -n "PasswordController" routes/web.php`. If matched, the canonical layout holds — proceed. If not matched, STOP and inspect `routes/web.php` and `routes/auth.php` to determine which controller backs `profile.password.update` (or equivalent) before deleting anything.

#### 4. Delete stripped views

**File**: delete the following files

**Intent**: Remove the Blade views for stripped flows.

**Contract**: These files no longer exist:
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/confirm-password.blade.php`
- `resources/views/auth/verify-email.blade.php`

#### 5. Strip `password_reset_tokens` from users migration

**File**: `database/migrations/0001_01_01_000000_create_users_table.php`

**Intent**: Edit the existing migration in place (greenfield — no environment has migrated this yet) to remove the `password_reset_tokens` table creation in `up()` and its drop in `down()`. Leave `users` and `sessions` blocks intact.

**Contract**: The `Schema::create('password_reset_tokens', ...)` block (currently lines 24-28) is removed from `up()`. The matching `Schema::dropIfExists('password_reset_tokens');` is removed from `down()`. After `php artisan migrate:fresh`, `password_reset_tokens` table does NOT exist; `users` and `sessions` do.

#### 6. Trim Pest test suite

**File**: `tests/Feature/Auth/`

**Intent**: Delete Pest test files for stripped features. Keep tests for the kept flows.

**Contract**: These test files are deleted:
- `tests/Feature/Auth/PasswordResetTest.php` — covers the stripped reset flow.
- `tests/Feature/Auth/PasswordConfirmationTest.php` — covers the stripped `password.confirm` middleware flow.
- `tests/Feature/Auth/EmailVerificationTest.php` — covers the stripped verification flow.

**Explicitly KEEP**:
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/PasswordUpdateTest.php` — in the standard Breeze 2.x Blade install this is the **Profile password-change** test (it pairs with the kept `Auth/PasswordController.php`). Confirm by `grep -n "current_password\|/password" tests/Feature/Auth/PasswordUpdateTest.php` — a current-password check and a POST to the Profile password endpoint mean it's the Profile flow; keep it.
- `tests/Feature/ProfileTest.php` (top-level — covers Profile name/email update + account delete).

### Success Criteria:

#### Automated Verification:

- Pest suite still green: `composer test` exits 0
- Lint clean: `./vendor/bin/pint --test` exits 0
- No stripped routes leak: `php artisan route:list | grep -E "password\.|verification\."` returns no rows
- Fresh migration works: `php artisan migrate:fresh` exits 0
- Fresh schema is correct: `php artisan db:show --counts` (or sqlite `.schema`) does not list `password_reset_tokens`

#### Manual Verification:

- `User::class` no longer mentions `MustVerifyEmail` anywhere in the app
- Visiting `/forgot-password` returns 404 (route gone)
- Visiting `/email/verify` returns 404 (route gone)
- Visiting `/register` still works and creates an account that lands on `/dashboard` (no verification interception)

**Implementation Note**: After this phase pause for manual confirmation that the stripped flows are genuinely gone and the remaining flows still work end-to-end before proceeding to Phase 3.

---

## Phase 3: Wire to welcome page + final verification

### Overview

Confirm the existing `resources/views/welcome.blade.php` integrates cleanly with Breeze's named routes, ensure `/dashboard` renders the placeholder, and do a final pass over tests + lint + smoke flow. Open the PR.

### Changes Required:

#### 1. Welcome page integration check

**File**: `resources/views/welcome.blade.php`

**Intent**: Verify the existing `@if (Route::has('login'))` block and `@auth` / `@guest` branches render correctly against Breeze's `login` and `dashboard` named routes. If Breeze's install overwrote `welcome.blade.php` (it sometimes does), restore the pre-install file from the current branch with `git restore -- resources/views/welcome.blade.php` — the committed version already contains the auth-aware nav at lines 22-48. Do NOT hand-reconstruct the file.

**Contract**: Visiting `/` while unauthenticated shows "Log in" + "Register" links pointing to `route('login')` and `route('register')`. Visiting `/` while authenticated shows a "Dashboard" link pointing to `route('dashboard')`. The body content matches the pre-Breeze state of `welcome.blade.php`.

#### 2. Dashboard placeholder

**File**: `resources/views/dashboard.blade.php`

**Intent**: Confirm the Breeze-shipped dashboard view exists and renders `"You're logged in!"` (or similar). If empty or generic, add a one-line greeting `"Hello, {{ Auth::user()->name }} — your garden log will live here."` so the slice is visibly successful end-to-end. Leave the structure of the file untouched so S-01 can extend it cleanly.

**Contract**: `/dashboard` (auth-protected) renders a page including the user's name. `/dashboard` while unauthenticated redirects to `/login`.

#### 3. Add one explicit Pest test for the dashboard gate

**File**: `tests/Feature/DashboardTest.php` (new)

**Intent**: Cover the auth-middleware gate explicitly — guests redirected to login, authenticated users see the dashboard. Breeze's default tests cover register/login/logout but not necessarily a custom guard check we depend on for every downstream slice.

**Contract**: One Pest file with two tests: `it redirects guests from dashboard to login` and `it renders dashboard for authenticated users`.

### Success Criteria:

#### Automated Verification:

- Full test suite green: `composer test` exits 0
- Lint clean: `./vendor/bin/pint` (apply mode) shows no changes pending
- `php artisan route:list` final state: shows `register`, `login`, `logout`, `dashboard`, `profile.*` — and only those

#### Manual Verification:

- Cold-start smoke: `composer setup && composer dev`, then in browser:
  1. Visit `/` → see Login + Register links
  2. Click Register → fill form → land on `/dashboard` showing "Hello, {name}"
  3. Click "Log Out" → return to `/`
  4. Click Login → enter same credentials → land on `/dashboard`
  5. Visit `/profile` → see profile form, change password works
  6. Visit `/forgot-password` (typed directly) → 404
- PR opened against `master`; CI green (per lessons rule)

**Implementation Note**: After this phase pause for final manual confirmation. Once accepted, this completes F-01 in the roadmap.

---

## Testing Strategy

### Unit Tests:

- Not needed for this slice — Breeze controllers are thin wrappers; auth logic lives in framework code already covered upstream.

### Integration / Feature Tests (Pest):

- **Registration**: valid input creates user + auto-logs in + redirects to dashboard; missing/invalid fields show errors; duplicate email rejected; password confirmation mismatch rejected.
- **Authentication**: valid credentials log in + redirect to dashboard; invalid credentials show error; rate limiting respected (Breeze default).
- **Logout**: POST `/logout` ends session, redirects to `/`.
- **Dashboard gate**: guest → redirect to `/login`; authenticated → 200.
- **Profile**: name/email update works; password change works.

### Manual Testing Steps:

1. Cold install via `composer setup` — verify a fresh contributor can boot the app.
2. Register, log out, log in — happy path.
3. Try the stripped URLs (`/forgot-password`, `/email/verify`) — confirm 404.
4. Visit `/dashboard` unauthenticated — confirm redirect to `/login`.
5. Change password via `/profile` — confirm new password works after logout/login cycle.

## Performance Considerations

- BCRYPT_ROUNDS=4 (`phpunit.xml`) keeps test-suite hashing fast. Production uses the default (10–12) via `config/hashing.php`. No change needed.
- Sessions use database driver (per CLAUDE.md). Acceptable for medium-scale traffic in PRD target.

## Migration Notes

- Greenfield: no production data to preserve. The original users migration is edited in place; no separate drop migration is created. After Phase 2 lands, anyone running `php artisan migrate:fresh` gets the trimmed schema.
- If we later add password reset back, we will write a new migration to re-create `password_reset_tokens` — clean reversal.

## References

- Roadmap entry: `context/foundation/roadmap.md` (F-01: Auth scaffold)
- PRD: `context/foundation/prd.md` (FR-001 / FR-002 / FR-003, Access Control)
- Tech stack: `context/foundation/tech-stack.md` (has_auth: true, language: php, starter: laravel)
- Lessons: `context/foundation/lessons.md` (always branch + PR)
- Project conventions: `CLAUDE.md` (Artisan-first; `composer test`; SQLite dev DB)
- Default users migration: `database/migrations/0001_01_01_000000_create_users_table.php:14-37`
- Welcome page expecting Breeze named routes: `resources/views/welcome.blade.php:22-30`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Install Breeze with Pest

#### Automated

- [x] 1.1 Composer install completes: `composer install` exits 0
- [x] 1.2 Breeze install completes: `php artisan breeze:install blade --pest` exits 0
- [x] 1.3 Migrations apply cleanly: `php artisan migrate` exits 0
- [x] 1.4 Frontend builds: `npm install && npm run build` exit 0
- [x] 1.5 Pest suite passes (hard gate): `composer test` exits 0 and the output banner reads "Pest" (not "PHPUnit")
- [x] 1.6 Lint clean: `./vendor/bin/pint --test` exits 0
- [x] 1.7 Routes present: `php artisan route:list` includes `register`, `login`, `logout`, `dashboard`
- [x] 1.8 Auth routes live in `routes/auth.php`: file exists AND `routes/web.php` requires it

#### Manual

- [x] 1.9 Dev server starts: `composer dev` boots without errors
- [x] 1.10 Browser smoke test: `/register`, `/login`, submit valid credentials → `/dashboard`; logout → `/`
- [x] 1.11 Pest is the test runner: `composer test` output shows the Pest ASCII banner and uses Pest `it()/test()` syntax in `tests/Feature/Auth/`

### Phase 2: Trim scope (remove verify, reset, confirm)

#### Automated

- [ ] 2.1 Pest suite still green: `composer test` exits 0
- [ ] 2.2 Lint clean: `./vendor/bin/pint --test` exits 0
- [ ] 2.3 No stripped routes leak: `php artisan route:list | grep -E "password\.|verification\."` returns no rows
- [ ] 2.4 Fresh migration works: `php artisan migrate:fresh` exits 0
- [ ] 2.5 Fresh schema is correct: `password_reset_tokens` is absent from `db:show --counts`

#### Manual

- [ ] 2.6 `User::class` no longer mentions `MustVerifyEmail` anywhere in the app
- [ ] 2.7 Visiting `/forgot-password` returns 404
- [ ] 2.8 Visiting `/email/verify` returns 404
- [ ] 2.9 Visiting `/register` still works and creates an account that lands on `/dashboard` (no verification interception)

### Phase 3: Wire to welcome page + final verification

#### Automated

- [ ] 3.1 Full test suite green: `composer test` exits 0
- [ ] 3.2 Lint clean: `./vendor/bin/pint` shows no pending changes
- [ ] 3.3 `php artisan route:list` final state: shows only `register`, `login`, `logout`, `dashboard`, `profile.*`

#### Manual

- [ ] 3.4 Cold-start smoke flow: register → dashboard → logout → login → dashboard → profile password change works
- [ ] 3.5 Stripped URLs return 404 (`/forgot-password`, `/email/verify`)
- [ ] 3.6 PR opened against `master`; CI green
