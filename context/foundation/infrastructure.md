---
project: garden-log
researched_at: 2026-05-24T00:00:00Z
recommended_platform: Railway
runner_up: DigitalOcean App Platform
context_type: mvp
tech_stack:
    language: php
    framework: laravel
    runtime: php-fpm
    database: mysql
---

## Recommendation

**Deploy on Railway.**

Railway's official Laravel 13 template (PHP-FPM + Caddy, Railpack auto-detection) eliminates all bootstrap friction for
this stack. Managed MySQL runs as a co-located Railway service, Valkey/Redis is available, and persistent volumes handle
database storage — satisfying the co-location preference without manual infrastructure work. At MVP scale the Hobby
plan ($5/month) covers typical compute consumption, making Railway the cheapest full-stack option that doesn't require
raw-VPS operational overhead. Laravel Cloud was the initial top pick but was swapped after the anti-bias cross-check
surfaced its missing CLI rollback command, usage-based surprise billing risk, and Flex hibernation cold starts — all of
which are platform-level constraints, not configuration fixes.

## Platform Comparison

| Platform                  | CLI-first  | Managed    | Agent docs | Deploy API | MCP        | PHP/MySQL     | Est. cost |
|---------------------------|------------|------------|------------|------------|------------|---------------|-----------|
| **Railway**               | ✅          | ✅          | ✅          | ✅          | ⚠️ Preview | ✅ native      | $5–15/mo  |
| Laravel Cloud             | ✅          | ✅          | ✅          | ✅          | ⚠️ Partial | ✅ native      | $5–25/mo  |
| DigitalOcean App Platform | ⚠️ Partial | ✅          | ✅          | ✅          | ✅ GA       | ✅ managed     | $20+/mo   |
| Fly.io                    | ✅          | ✅          | ⚠️ Partial | ✅          | ⚠️ Pre-rel | ⚠️ MySQL beta | $5–15/mo  |
| Render                    | ⚠️ Partial | ✅          | ✅          | ⚠️ Partial | ✅ GA       | ⚠️ MySQL DIY  | $22+/mo   |
| Hetzner + Forge           | ⚠️ Partial | ⚠️ Partial | ⚠️ Partial | ⚠️ Partial | ❌          | ✅ self-mgd    | $17+/mo   |

**Hard filters applied**: Vercel, Netlify, Cloudflare Workers dropped — JavaScript-only runtimes, incompatible with
PHP/Laravel.

**Soft weights applied**:

- Cost-first → favored Railway ($5/mo) over DigitalOcean ($20/mo minimum)
- Co-location preferred → required managed MySQL from the same platform (ruled out Render, penalized Fly.io)
- Single region → edge-native platforms (Cloudflare) gained no advantage
- Laravel Cloud familiarity → broke ties in favor of Laravel Cloud initially; user chose to swap to Railway after
  cross-check

### Shortlisted Platforms

#### 1. Railway (Recommended)

Wins on cost ($5/mo Hobby covers a small Laravel app), co-located managed MySQL as a first-class service, and the most
comprehensive CLI of all platforms researched — including `railway rollback`. Official Laravel 12/13 template
auto-detects the framework and configures PHP-FPM + Caddy. Full LLM-readable docs (llms.txt, llms-full.txt, per-page
markdown). MCP server for Claude Code exists (`@railway/mcp-server`) but is preview/WIP — the CLI is the reliable
automation path.

#### 2. DigitalOcean App Platform

Strong GA MCP server (`digitalocean-mcp`) with remote MCP, the most production-proven of the three, and managed MySQL as
a first-class add-on ($15/month). Loses on minimum monthly cost ($20+/month for web + MySQL vs. Railway's $5) and the
`doctl` CLI lacks a direct rollback subcommand (requires REST API or dashboard). PHP support is via Heroku buildpack (
GA) — works well but adds an abstraction layer.

#### 3. Laravel Cloud

Native Laravel platform with zero-config deployment, managed MySQL + Valkey + storage co-located, llms.txt docs, and
user familiarity. Dropped from top recommendation after anti-bias cross-check: no `cloud rollback` CLI command,
usage-based billing without a hard monthly cap (surprise bill risk for AI-driven traffic), Flex instance hibernation
causing cold starts on personal apps, and platform maturity risk (pricing and feature boundaries have changed repeatedly
since the 2025 launch).

## Anti-Bias Cross-Check: Railway

The cross-check on the initial top pick (Laravel Cloud) caused a swap to Railway. The following cross-check was then run
on Railway before finalising.

### Devil's Advocate — Weaknesses

1. **Redis socket instability** — Community reports from 2025–2026 describe non-deterministic socket timeout errors on
   Railway's Redis service. Any Redis-backed feature (cache, sessions, queues) may exhibit intermittent failures. For
   GardenLog's Claude API integration, this could cause duplicate API calls (billed twice) or stale cached responses.

2. **Ephemeral filesystem with silent failure modes** — Laravel's defaults (`LOG_CHANNEL=single`,
   `FILESYSTEM_DISK=local`) both write to the container filesystem, which is destroyed on every redeploy. Nothing in
   Railway's onboarding flow catches this. A developer used to local dev will silently lose logs and uploaded files on
   first deploy.

3. **Laravel Sail/Docker Compose incompatibility** — Railway doesn't support `docker-compose.yml`. Sail-based local dev
   workflows must be adapted: each service (app, MySQL, Redis) becomes a separate Railway service. Local-to-production
   parity breaks.

4. **MCP server is preview/WIP** — `@railway/mcp-server` is self-described in Railway's docs as "a work in progress."
   Agent automation via MCP may have gaps or breaking changes. The CLI (`railway`) is the reliable path.

5. **`railway up` uploads local filesystem, not Git** — A misconfigured `.railwayignore` can bundle `.env` or vendor/
   into the deploy artifact, leaking secrets. GitHub-connected deploy mode avoids this but requires repo connection.

### Pre-Mortem — How This Could Fail

The team deployed GardenLog on Railway, drawn by the clean Laravel template and $5/month Hobby plan. Six months later,
compounding issues had eroded confidence.

The first failure was invisible: `LOG_CHANNEL` was never changed from `single`. Railway captures only stdout/stderr — so
every AI query failure, exception, and slow database query had been silently dropped. When users reported incorrect AI
responses about their garden history, there were no logs to debug with. Establishing the root cause took four days.

Second: the developer added garden photo upload as a new feature. `FILESYSTEM_DISK` was still `local`. The first deploy
after the feature shipped deleted every uploaded image. Recovering user trust took weeks, and the apology email required
manually re-requesting photos from users.

Third: Redis socket timeouts surfaced as intermittent cache misses in the AI query feature. On bad days the feature
double-called the Claude API, billing twice for the same query. Switching to database sessions and disabling the
response cache resolved the billing leak but was a regression from the planned architecture.

Fourth: a gardening event drove an unexpected traffic spike mid-month. The Hobby plan's $5 credit ran out. The service
paused automatically. The developer woke to an inbox of "app is down" messages and had to upgrade to the Pro plan under
pressure.

### Unknown Unknowns

1. **`LOG_CHANNEL=stderr` is mandatory before first deploy** — Railway captures only stdout/stderr. Laravel's default
   `LOG_CHANNEL=single` writes to a file that evaporates on restart. The app runs silently with no observable errors in
   the Railway dashboard until this is set.

2. **`php artisan storage:link` must run on every deploy** — The `public/storage` → `storage/app/public` symlink doesn't
   survive container rebuilds. Must be added to the deploy/start command. Forgetting causes uploaded file serving to
   silently 404.

3. **Hobby plan pauses the app when
   the $5 monthly credit is exhausted** — Unlike a paid-overage model, Railway Hobby stops your service entirely when the credit runs out. For a personal app expected to be reliably available, this is a production risk in above-average traffic months. Upgrade to Pro ($
   20/month) to get overage billing instead of hard shutoff.

4. **Railway builds from local filesystem tarball, not Git, when using `railway up`** — A `.railwayignore` that doesn't
   exclude `.env` ships secrets in the deploy artifact. Connecting the project to a GitHub repo and using the
   GitHub-triggered deploy mode avoids this entirely and is the safer pattern.

5. **MySQL Volume IOPS limits on Hobby are undocumented** — Railway MySQL runs on a persistent Volume. High-frequency
   writes (logging to disk, DB write-ahead logs) can hit implicit I/O throttling that surfaces as slow queries. Not
   documented in pricing; only discoverable under load.

## Operational Story

- **Preview deploys**: Railway supports environment-level deploys; create a separate `staging` environment via
  `railway environment --name staging` and deploy the same service there. Branch-triggered preview URLs require
  connecting to GitHub and setting a branch deploy rule in the service settings.
- **Secrets**: Environment variables live in Railway's project vault, set via `railway variables set KEY=value` or the
  dashboard. Agent-accessible via the MCP `set-variables` tool (preview) or CLI. Variables are injected at runtime, not
  baked into the image — safe for secrets rotation without redeploy.
- **Rollback**: `railway rollback` reverts to the previous successful deployment. Does not roll back the database
  schema — if a migration ran in the bad deploy, database state must be recovered separately via
  `php artisan migrate:rollback` or a volume snapshot restore.
- **Approval**: Agents may perform unattended: `railway up`, `railway deploy`, `railway logs`, `railway variables set`,
  `railway rollback`. Human-only: deleting a project or service, upgrading billing plan, connecting/disconnecting a
  GitHub repo, creating a new environment.
- **Logs**: `railway logs` streams runtime logs live. `railway logs --build` for build output.
  `railway logs --lines 100 --since 1h` for historical. The MCP `get-logs` tool (preview) gives structured log access
  from Claude Code.

## Risk Register

| Risk                                                                 | Source           | Likelihood | Impact | Mitigation                                                                                                                                          |
|----------------------------------------------------------------------|------------------|------------|--------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| Redis socket timeouts causing cache misses or duplicate AI API calls | Devil's advocate | M          | M      | Use `CACHE_DRIVER=database` and `SESSION_DRIVER=database` instead of Redis for MVP; avoids Redis entirely until stability issues are resolved       |
| Silent log loss from `LOG_CHANNEL=single`                            | Unknown unknowns | H          | H      | Set `LOG_CHANNEL=stderr` in production env vars before first deploy; enforce in the `.env.example` committed to repo                                |
| Ephemeral filesystem destroys uploaded files on redeploy             | Pre-mortem       | H          | H      | Set `FILESYSTEM_DISK=s3` and configure Railway Buckets (or Cloudflare R2) before enabling any file upload feature                                   |
| Hobby plan hard-pauses the app when $5 credit is exhausted           | Unknown unknowns | M          | H      | Monitor Railway usage dashboard weekly; set a billing alert; upgrade to Pro ($20/mo) before first public launch                                     |
| `.env` leaked in `railway up` deploy artifact                        | Devil's advocate | L          | H      | Connect project to GitHub repo and use GitHub-triggered deploys rather than `railway up` from local machine                                         |
| `storage:link` symlink lost on redeploy breaks file serving          | Unknown unknowns | H          | M      | Add `php artisan storage:link` to the `startCommand` in Railway service settings                                                                    |
| MCP server breaking changes (preview status)                         | Research finding | M          | L      | Pin `@railway/mcp-server` version; use `railway` CLI as the primary automation path, MCP as supplementary                                           |
| MySQL Volume IOPS throttling under sustained write load              | Unknown unknowns | L          | M      | Keep write patterns light at MVP; monitor query times; migrate to Railway's managed Postgres if MySQL shows throttling                              |
| Docker Compose / Sail parity break                                   | Devil's advocate | M          | L      | Use Railway's multi-service architecture in local dev via Railway's local dev proxy, or use plain `php artisan serve` + local MySQL for development |

## Getting Started

1. **Install the Railway CLI** (verified for 2026): `npm i -g @railway/cli` then `railway login`
2. **Link the GitHub repo** (safer than `railway up`): in the Railway dashboard, create a new project → "Deploy from
   GitHub repo" → select the `garden-log` repo
3. **Add MySQL service**: in the Railway project, click "Add Service" → "Database" → "MySQL 8" — Railway auto-injects
   `MYSQL_URL` and `DATABASE_URL` into the app service
4. **Set required env vars** before first deploy:
    - `APP_KEY` — generate with `php artisan key:generate --show` and paste the value
    - `APP_ENV=production`
    - `LOG_CHANNEL=stderr`
    - `CACHE_DRIVER=database`
    - `SESSION_DRIVER=database`
    - `QUEUE_CONNECTION=database`
5. **Set the start command** in the Railway service to run migrations and link storage on boot:
   ```
   php artisan migrate --force && php artisan storage:link && php-fpm
   ```
6. **Verify the deploy**: `railway logs --lines 50` should show Laravel boot output with no file-driver warnings; the
   Railway dashboard should show a green health check on the app service

## Out of Scope

The following were not evaluated in this research:

- Docker image configuration (Railway's Railpack handles this automatically for Laravel)
- CI/CD pipeline setup (GitHub-triggered deploys via Railway cover the MVP case)
- Production-scale architecture (multi-region, HA, DR)
