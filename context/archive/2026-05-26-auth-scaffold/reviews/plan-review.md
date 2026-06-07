<!-- PLAN-REVIEW-REPORT -->
# Plan Review: Auth Scaffold Implementation Plan

- **Plan**: `context/changes/auth-scaffold/plan.md`
- **Mode**: Deep
- **Date**: 2026-05-26
- **Verdict**: REVISE → SOUND (after triage)
- **Findings**: 0 critical, 1 warning, 2 observations

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| End-State Alignment | PASS |
| Lean Execution | PASS |
| Architectural Fitness | PASS |
| Blind Spots | WARNING |
| Plan Completeness | WARNING |

## Grounding

6/6 paths ✓ (User.php, users migration, routes/web.php, welcome.blade.php, composer.json, phpunit.xml), 3/3 symbols ✓ (`MustVerifyEmail` on User.php:15, `password_reset_tokens` migration block lines 24-28, `BCRYPT_ROUNDS=4` in phpunit.xml:23), brief↔plan ✓.

## Findings

### F1 — Profile password-change roster is muddled (PasswordController + PasswordConfirmationTest)

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Blind Spots
- **Location**: Phase 2 #3 (controllers) and Phase 2 #6 (tests)
- **Detail**: Phase 2 #3 listed `PasswordController.php` with "verify before deletion; keep if wired from `routes/web.php`, delete otherwise". Phase 2 #6 listed `PasswordConfirmationTest.php` in BOTH the delete list and the keep list. In standard Breeze 2.x: `PasswordController` IS the Profile password-update controller (keep); `PasswordConfirmationTest` covers the stripped `password.confirm` middleware (delete); `PasswordUpdateTest` covers Profile password change (keep). Wrong guesses would silently break the kept Profile flow.
- **Fix**: Bake the canonical Breeze 2.x roster into the plan — explicitly KEEP `PasswordController` + `PasswordUpdateTest`, explicitly DELETE `PasswordConfirmationTest`. Add a `grep -n "PasswordController" routes/web.php` cross-check so Phase 2 fails closed if Breeze diverges from the canonical layout.
  - Strength: Removes the only judgment call in the plan; aligns with canonical Breeze layout.
  - Tradeoff: Locks in a Breeze 2.x layout assumption (cheap to refresh if it changes).
  - Confidence: HIGH — current Breeze 2.x layout is well-documented.
  - Blind spot: Not verified against the exact Breeze tag this plan installs; grep cross-check covers that.
- **Decision**: FIXED — applied to plan.md Phase 2 #3 (explicit KEEP block + grep cross-check) and Phase 2 #6 (explicit DELETE/KEEP lists with verification grep on PasswordUpdateTest)

### F2 — welcome.blade.php restore mechanism unspecified

- **Severity**: 📝 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: Phase 3 #1
- **Detail**: Phase 3 #1 said "if Breeze overwrote `welcome.blade.php`, restore the existing content" but didn't say how. The current file is committed on the active branch with the auth-aware nav at lines 22-48; without explicit guidance the agent could try to hand-reconstruct it.
- **Fix**: Specify `git restore -- resources/views/welcome.blade.php` and forbid hand-reconstruction.
- **Decision**: FIXED — Phase 3 #1 now specifies the `git restore` command and forbids hand-reconstruction.

### F3 — Post-install check that routes/web.php loads routes/auth.php

- **Severity**: 📝 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: Phase 1 Success Criteria
- **Detail**: Plan noted Breeze writes `routes/auth.php` and `routes/web.php` requires it. Current `routes/web.php` is 7 lines with no require. Old Success Criteria 1.7 only checked `route:list` — would pass even if Breeze inlined routes into `routes/web.php`, leaving Phase 2's edit target missing.
- **Fix**: Add Success Criterion: `routes/web.php` contains a require/include of `routes/auth.php`, and `routes/auth.php` exists.
- **Decision**: FIXED — added new Phase 1 Automated criterion 1.8 ("Auth routes live in `routes/auth.php`: file exists AND `routes/web.php` requires it"); Manual progress items renumbered 1.9-1.11.
