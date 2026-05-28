<!-- PLAN-REVIEW-REPORT -->

# Plan Review: AI Recall Loop

- **Plan**: context/changes/ai-recall-loop/plan.md
- **Mode**: Deep
- **Date**: 2026-05-27
- **Verdict**: REVISE
- **Findings**: 1 critical, 1 warning, 1 observation

## Verdicts

| Dimension             | Verdict |
|-----------------------|---------|
| End-State Alignment   | PASS    |
| Lean Execution        | PASS    |
| Architectural Fitness | PASS    |
| Blind Spots           | FAIL    |
| Plan Completeness     | WARNING |

## Grounding

Grounding: 7/7 paths ✓, 3/3 symbols ✓, brief↔plan ✓

## Findings

### F1 — Missing Content-Type header in fetch request

- **Severity**: ❌ CRITICAL
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Blind Spots
- **Location**: Phase 3, Change 1 — AI Search Bar Partial (Contract)
- **Detail**: The Alpine.js fetch contract specifies headers `{ 'X-CSRF-TOKEN': ..., 'Accept': 'application/json' }`
  with `body: JSON.stringify({ question: query })`. The Content-Type header is missing. Without
  `Content-Type: application/json`, the browser sends `text/plain` by default. Laravel's request object won't parse the
  body as JSON, so `$request->input('question')` will be null and validation will reject every request with 422 "The
  question field is required." The entire feature silently breaks at the HTTP layer.
- **Fix**: Add `'Content-Type': 'application/json'` to the headers object in the fetch contract.
- **Decision**: FIXED

### F2 — Progress section missing 2 Phase 3 manual verification items

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: ## Progress → Phase 3: Frontend UI → Manual
- **Detail**: Phase 3 Success Criteria lists 8 manual verification bullets but the Progress section only has 6 entries (
  3.4–3.9). Two criteria are missing from tracking: "Retry button re-submits the same question" and "Empty query
  submission is prevented (HTML validation + Alpine guard)". /10x-implement will not track these steps and may skip
  verifying them.
- **Fix**: Add `- [ ] 3.10 Retry button re-submits the same question` and `- [ ] 3.11 Empty query submission prevented`
  to the Phase 3 Manual subsection in Progress.
- **Decision**: FIXED

### F3 — No rate limiting on AI endpoint

- **Severity**: 💡 OBSERVATION
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Blind Spots
- **Location**: Phase 2, Change 3 — Route Registration
- **Detail**: POST /tasks/ask calls an external LLM API that costs money per request. Any authenticated user can submit
  unlimited queries. The plan doesn't mention rate limiting and "What We're NOT Doing" doesn't explicitly exclude it.
  For MVP with trusted beta users this may be fine, but if the app is open-registration, a single user could burn
  significant OpenRouter credits.
- **Fix A ⭐ Recommended**: Add a note to "What We're NOT Doing" acknowledging the risk and deferring rate limiting to a
  follow-up.
    - Strength: Keeps MVP scope clean; makes the trade-off explicit.
    - Tradeoff: Risk remains until the follow-up ships.
    - Confidence: HIGH — this is a documentation-only change.
    - Blind spot: None significant.
- **Fix B**: Add Laravel's built-in throttle middleware to the route.
    - Strength: One line (`->middleware('throttle:10,1')`) closes the risk immediately.
    - Tradeoff: Adds scope to Phase 2; needs a test and a UX decision for the 429 response.
    - Confidence: HIGH — Laravel throttle is well-documented.
    - Blind spot: Frontend needs to handle 429 responses gracefully.
- **Decision**: ACCEPTED
