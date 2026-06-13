---
name: changes-checklist
description: Turn a correction described directly by the user (optionally with a screenshot) into a structured, numbered checklist entry appended to context/proposed-changes/changes.md. Use when the user invokes /changes-checklist.
disable-model-invocation: true
argument-hint: [correction description]
---

# Changes Checklist Builder

Take a single correction that the user describes directly — optionally accompanied by a screenshot — and append it as
a structured, numbered entry to `context/proposed-changes/changes.md`.

There is no CRM integration. The user is the source of truth: they write the correction in plain language and may attach
a screenshot for context. One invocation handles exactly **one** correction.

## Input

The correction text comes from `$ARGUMENTS` and/or the surrounding conversation. The user may also paste or reference a
screenshot. Treat the screenshot as additional context that helps you understand and describe the correction.

## Your workflow

### Step 1: Ensure the target document and folders exist

- The document path is **always** `context/proposed-changes/changes.md`.
- If `context/proposed-changes/` does not exist, create it.
- If `changes.md` does not exist, you will create it in Step 5 with a header.

### Step 2: Determine the next entry number

- Read `changes.md` if it exists and find the highest existing entry number (entries start with `**#N — ...`).
- The new entry number is `highest + 1`. If the document is new or has no entries, start at `#1`.
- Numbering is continuous across the life of the document (it does not reset per session).

### Step 3: Handle a screenshot (if provided)

- Use the screenshot to better understand and describe **gdzie** and **opis**.
- If the screenshot is a file on disk:
  - Ensure `context/proposed-changes/screenshots/` exists.
  - Copy the file there, named `NN-slug.ext` where `NN` is the zero-padded entry number and `slug` is a short
    kebab-case label derived from the title (e.g. `01-raporty-eksport.png`).
  - Reference it in the entry as `![screen](screenshots/NN-slug.ext)`.
- If the screenshot is only pasted into the conversation (no file on disk), use it as context only and note in your
  reply that no image file was linked.

### Step 4: Build the entry — and never guess

Extract these fields from what the user wrote (and the screenshot):

- **title** — a short title for the correction, derived from the user's description.
- **gdzie** (where) — where in the application the issue occurs.
- **opis** (description) — concise description of the problem AND the intended fix.

**CRITICAL RULES:**

- Do NOT guess or infer information that is not stated by the user or clearly visible in the screenshot.
- If you cannot determine `gdzie` or `opis`, ASK the user.
- If the correction is ambiguous or seems to contain more than one issue, ASK the user to clarify or split it (one
  invocation = one correction).
- Keep the description concise but complete — capture the problem AND the solution.
- Do NOT add status indicators (✅, 🔄, etc.) unless the user explicitly asks for them.

### Step 5: Show the entry and confirm

Present the formatted entry to the user and ask for confirmation before writing. The format is:

```
**\#N \- title**  
**gdzie**: location in the application  
**opis**: concise description of the problem and the fix
```

If a screenshot was saved, add the image line:

```
![screen](screenshots/NN-slug.ext)
```

If the correction has sub-items to verify, add a `**do sprawdzenia**:` section with checkboxes:

```
**do sprawdzenia**:
- [ ] item to verify
```

### Step 6: Write to the document

- If `changes.md` exists, read it first, then append the new entry at the end (before any closing section like a
  trailing `---`).
- If `changes.md` does not exist, create it with a top-level header (e.g. `# Proposed changes`) followed by the first
  entry.
- The document path is always `context/proposed-changes/changes.md`.

## Conversation style

- Speak Polish with the user.
- Ask short, focused questions — only when something is genuinely unclear.
- Never guess — if `gdzie`/`opis` or the screenshot intent is unclear, ask.
- After writing, confirm what was added (entry number and title) and where (`context/proposed-changes/changes.md`).

## Example output format

```markdown
**\#1 \- pobieranie raportów**  
**gdzie**: raporty → eksport do XLS/CSV  
**opis**: pobieranie raportów do XLS i CSV zapisywało plik jako .download zamiast poprawnego rozszerzenia  
![screen](screenshots/01-raporty-eksport.png)

**do sprawdzenia**:
- [ ] poprawne rozszerzenie pliku przy eksporcie
```