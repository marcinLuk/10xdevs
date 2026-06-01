# Logo PNG Swap — Plan Brief

> Full plan: `context/changes/ui-fixes-round-1/plan.md`

## What & Why

Replace the inline SVG leaf icon in `<x-application-logo>` with the existing `resources/images/logo.png` (GardenLog wordmark). The PNG is already in the repo but unused.

## Starting Point

The logo component renders an SVG leaf. Three views consume it: welcome page, guest layout (auth pages), and navigation bar. All pass color classes (`text-green-600`, `fill-current`) that only work with SVG.

## Desired End State

The GardenLog PNG wordmark appears everywhere the SVG leaf used to. Stale SVG color classes are removed from consumers.

## Scope

**In scope:** Swap SVG → PNG in the component, clean up 3 consumer views

**Out of scope:** Welcome page layout, button fixes, dark-mode variants, new logo sizes

## Phases at a Glance

| Phase | What it delivers | Key risk |
|-------|-----------------|----------|
| 1. Swap logo + clean up | PNG renders in all 3 locations | None — single component change |

**Prerequisites:** None
**Estimated effort:** ~1 session, single phase

## Success Criteria (Summary)

- PNG wordmark logo visible on welcome, login, register, and navigation bar
- No broken views or build errors
