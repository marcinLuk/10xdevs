# AI Grounding Eval (promptfoo)

Proves the **live** OpenRouter model honours the grounding contract for Risk #1:
it cites only dates/events present in the gardener's task log, and says "not
found" when there is no match. This is the thing the Pest suite structurally
cannot prove — a `Prism::fake` only tests the mock. Here we drive the **real**
prompt assembly + `AiRecallService` against the live model.

- `promptfooconfig.yaml` — HTTP provider → the env-guarded `/eval/grounding`
  route; OpenRouter judge for `llm-rubric`.
- `grounding-cases.yaml` — the golden dataset (3 axes: present / absent / partial).

## How it avoids drift

promptfoo does **not** contain a copy of the prompt. Its HTTP provider POSTs each
case to `POST /eval/grounding` (registered only under `APP_ENV=local|testing`,
token-gated), which seeds a transient user + tasks, runs the actual
`AiRecallService` (real Blade template + real OpenRouter call), returns the
answer, and rolls the seed data back. The eval therefore exercises exactly what
production runs.

## Prerequisites

1. **Node 22 LTS** (or 20.20+/24). **Do not use Node 26** — its experimental fetch
   decompression terminates the judge's gzip'd OpenRouter response
   (`TypeError: terminated` on an HTTP 200). Check with `node --version`.
2. **Dependencies installed:** `npm install` (promptfoo is a devDependency).
3. **The app served** — the Docker/nginx stack on **port 8001**:
   ```bash
   docker compose up -d
   ```
   (Or `composer dev`, which serves on 8000 — then set `EVAL_BASE_URL` below.)
4. **Environment variables:**
   | Var | Purpose |
   |-----|---------|
   | `OPENROUTER_API_KEY` | subject calls (via the route) **and** the `llm-rubric` judge. Must be set in the app's environment (the route makes the real call) and in your shell (the judge call). |
   | `EVAL_ROUTE_TOKEN` | shared secret; must match the app's `.env`. Sent as the `X-Eval-Token` header. |
   | `EVAL_BASE_URL` | optional. Defaults to `http://localhost:8001`. Set to `http://localhost:8000` if serving via `composer dev`. |

## Run

From the project root:

```bash
npm run eval:grounding
```

This prints a per-case pass/fail table. Each case is graded by an `llm-rubric`
(model-graded, threshold 0.7) plus at least one deterministic guard
(`contains` the real date / `not-contains` / `not-icontains`).

PowerShell example (Docker stack):

```powershell
$env:EVAL_ROUTE_TOKEN = "<same-as-app-.env>"
$env:OPENROUTER_API_KEY = "<your-key>"   # if not already in your shell
npm run eval:grounding
```

## Before trusting the result

Two checks keep the gate honest:

- **Sabotage / teeth.** Weaken the grounding rules in
  `resources/views/prompts/garden-recall.blade.php` (e.g. delete the `NEVER
  invent…` and `say so explicitly…` lines), re-run, and confirm **at least one**
  absent/partial case flips to FAIL. Then revert:
  ```bash
  git checkout -- resources/views/prompts/garden-recall.blade.php
  ```
  A run that is green-by-default proves nothing.
- **Judge calibration.** Read the `reason` text on 2–3 verdicts and confirm a
  human would agree before relying on the 0.7 threshold.

## Adding a case

Append to `grounding-cases.yaml` under the matching axis. Use **fixed ISO dates**;
for absent cases make the subject provably absent from that case's log and keep
the question date-free (so a correct "not found" answer contains no ISO date).

**Embed the log in the rubric** with `{{ tasks | dump }}` — promptfoo sends the
judge only the output and the rubric, not the task log, so without it the judge
cannot tell a quoted-from-log detail from an invention and will false-fail correct
answers. Always pair the rubric with a free deterministic guard.

See `context/foundation/test-plan.md` §6.4 for the broader pattern.
