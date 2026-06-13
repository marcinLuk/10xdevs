---
name: hook-creator
description: Create Claude Code hooks (command, http, mcp_tool, prompt, or agent) through a guided questionnaire, grounded in the bundled hooks reference.
disable-model-invocation: true
---

# Hook Creator

Your job is to help the user create a **Claude Code hook** and wire it into the right settings file.
Work strictly from the bundled reference — do **not** rely on memory or guess hook schemas, field
names, event names, or exit-code behavior.

## Step 0 — Always read the reference first

Before asking anything, read the bundled hooks documentation so every answer you give is grounded:

- Read [`hooks-reference.md`](hooks-reference.md) from this skill's own directory
  (`${CLAUDE_SKILL_DIR}/hooks-reference.md`).

It is a large file (~3000 lines). Read the sections relevant to what the user wants. Useful anchors:
**Hook lifecycle** (the event table), **Configuration** (matcher patterns, hook handler fields,
hook locations), **Hook input and output** (common input fields, exit codes, JSON output), and the
per-event **Hook events** sections further down. If you need a section that isn't in the first read,
read more of the file or grep it — never invent schema details.

## How to run: questionnaire

Gather requirements by asking **one question at a time**, waiting for the user's answer before the
next question. Keep each question short. Where helpful, give a brief **Explanation** and a
**Recommendation**, visibly separated from the question, e.g.:

```
**Explanation:** PreToolUse fires before a tool runs and can block it; PostToolUse fires after.
**Recommendation:** For lint/format-after-edit, use PostToolUse with an Edit|Write matcher.
```

Skip a question when the user's earlier answers already make it unambiguous, and confirm your
inference instead of re-asking.

### Questions to cover

Adapt the order and depth to the user's case, but make sure these are settled before you generate
anything:

1. **Goal** — what should the hook do, and what should trigger it? (the real-world outcome)
2. **Event** — which hook event fires it (e.g. `PreToolUse`, `PostToolUse`, `UserPromptSubmit`,
   `SessionStart`, `Stop`, `FileChanged`, …). Map the goal to the event using the lifecycle table.
3. **Matcher** — how to narrow it (tool name like `Bash` or `Edit|Write`, regex, MCP pattern like
   `mcp__memory__.*`, or none). Check the reference for what each event's matcher filters; some
   events don't support matchers.
4. **Handler type** — `command`, `http`, `mcp_tool`, `prompt`, or `agent`. Default to `command`
   unless the user needs an HTTP endpoint, an MCP tool, or LLM-based evaluation.
5. **`if` narrowing** (tool events only) — optional permission-rule filter like `Bash(git *)` or
   `Edit(*.ts)` to avoid spawning the handler on irrelevant calls.
6. **Behavior / blocking** — should it block, deny, warn, inject context, or just observe? Tie this
   to the correct exit-code or JSON-output mechanism for the chosen event (e.g. exit 2 to block on
   `PreToolUse`; `additionalContext` to feed the agent; `permissionDecision: "deny"`).
7. **Location** — which settings file: `~/.claude/settings.json` (all projects),
   `.claude/settings.json` (this project, committable), or `.claude/settings.local.json` (private).
8. **Script vs inline** — for `command` hooks, whether to write a separate script (recommended for
   anything non-trivial) under `.claude/hooks/` referenced via `${CLAUDE_PROJECT_DIR}`, or keep it
   inline. Prefer **exec form** (`command` + `args`) when referencing a path placeholder.

## Generating the hook

Once requirements are clear:

1. **Show the plan** — state the event, matcher, handler type, behavior, and target settings file in
   one short summary, and ask the user to confirm before writing.
2. **Write the configuration** — merge the hook into the chosen settings file's `hooks` object
   without clobbering existing hooks. If the file or `hooks` key doesn't exist, create it. Read the
   file first; preserve formatting and other keys.
3. **Write the script** if one is needed — create it under `.claude/hooks/`, make it read the event
   JSON from stdin (e.g. with `jq`), and use the exit codes / JSON output that match the event per
   the reference. On macOS/Linux, `chmod +x` the script.
4. **Mind the platform** — this is a Windows environment. Note Windows caveats from the reference
   (e.g. exec form can't spawn `.cmd`/`.bat` shims directly; use `node` + script path, or shell
   form; `shell: "powershell"` is available for command hooks).
5. **Explain how to verify** — tell the user they can inspect it with `/hooks`, and describe what
   action will trigger it. Direct edits to settings are normally picked up automatically.

## Rules

- Ground every schema/field/exit-code claim in `hooks-reference.md`. When unsure, re-read it.
- Never invent event names, matcher behavior, or output fields.
- Don't delete or overwrite the user's existing hooks; merge.
- Keep the hook as narrow as possible (matcher + `if`) so it doesn't fire on irrelevant events.
- Confirm before writing to any settings file.
