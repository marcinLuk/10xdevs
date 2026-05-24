---
bootstrapped_at: 2026-05-24T11:25:00Z
starter_id: laravel
starter_name: Laravel
project_name: garden-log
language_family: php
package_manager: composer
cwd_strategy: subdir-then-move
bootstrapper_confidence: verified
phase_3_status: ok
audit_command: "null"
---

## Hand-off

```yaml
starter_id: laravel
package_manager: composer
project_name: garden-log
hints:
    language_family: php
    team_size: solo
    deployment_target: self-host
    ci_provider: github-actions
    ci_default_flow: auto-deploy-on-merge
    bootstrapper_confidence: verified
    path_taken: standard
    quality_override: false
    self_check_answers: null
    has_auth: true
    has_payments: false
    has_realtime: false
    has_ai: true
    has_background_jobs: false
```

### Why this stack

Laravel is the natural fit for a solo PHP developer building a full-stack web app with auth and AI-powered search under
a tight 3-week after-hours timeline. It ships authentication scaffolding (Breeze/Fortify), Eloquent ORM for task
persistence, and a convention-based structure that AI coding agents navigate fluently thanks to massive training-data
coverage. The AI recall feature (FR-009/010) integrates via an LLM SDK querying against Eloquent-managed task history,
keeping the architecture simple and the data grounded. Self-hosted deployment on a VPS with Docker keeps costs
predictable and gives full control over the data layer — important given the privacy guardrail in the PRD.

## Pre-scaffold verification

| Signal      | Value   | Severity | Notes                                                                                |
|-------------|---------|----------|--------------------------------------------------------------------------------------|
| npm package | not run | n/a      | non-JS starter; npm check not applicable                                             |
| GitHub repo | not run | n/a      | docs_url (https://laravel.com/docs) is not a GitHub URL; no recency signal available |

## Scaffold log

**Resolved invocation**: `composer create-project laravel/laravel .bootstrap-scaffold --no-interaction --prefer-dist`
**Strategy**: subdir-then-move (scaffold into a temp directory then move files up)
**Exit code**: 0
**Laravel version installed**: v13.7.0 (laravel/framework v13.11.2)
**Files moved**: 22 items (files + directories) moved silently
**Conflicts (.scaffold siblings)**: `.env` → existing cwd `.env` preserved; scaffold copy saved as `.env.scaffold`
**.gitignore handling**: append-merged — cwd lines kept in order, scaffold lines de-duped and appended with
`# from laravel` separator
**.bootstrap-scaffold cleanup**: deleted

### Files moved silently

`README.md`, `app/`, `artisan`, `bootstrap/`, `composer.json`, `composer.lock`, `config/`, `database/`, `package.json`,
`phpunit.xml`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, `vite.config.js`, `.editorconfig`,
`.env.example`, `.gitattributes`, `.npmrc`

## Post-scaffold audit

**Tool**: skipped — no built-in audit tool for `php`
**Recommended external tool**: Roave's `security-advisories` Composer plugin (
`composer require --dev roave/security-advisories:dev-latest`) or `local-php-security-checker` (
`https://github.com/fabpot/local-php-security-checker`)

## Hints recorded but not acted on

| Hint                    | Value                |
|-------------------------|----------------------|
| bootstrapper_confidence | verified             |
| quality_override        | false                |
| path_taken              | standard             |
| self_check_answers      | null                 |
| team_size               | solo                 |
| deployment_target       | self-host            |
| ci_provider             | github-actions       |
| ci_default_flow         | auto-deploy-on-merge |
| has_auth                | true                 |
| has_payments            | false                |
| has_realtime            | false                |
| has_ai                  | true                 |
| has_background_jobs     | false                |

## Next steps

Next: a future skill will set up agent context (CLAUDE.md, AGENTS.md). For now, your project is scaffolded and
verified — happy hacking.

Useful manual steps in the meantime:

- `git init` (if you have not already) to start your own repo history.
- Review `.env.scaffold` — it contains the Laravel-generated `.env` with a freshly generated `APP_KEY`. You may want to
  merge relevant values (e.g. database credentials) into your existing `.env`.
- Run `npm install` to install the JS dev dependencies (`vite.config.js` + `package.json` are now in cwd).
- Address audit findings per your project's risk tolerance — no automated audit ran for PHP; see recommended external
  tools above.
