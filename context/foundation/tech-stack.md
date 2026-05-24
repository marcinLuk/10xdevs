---
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
---

## Why this stack

Laravel is the natural fit for a solo PHP developer building a full-stack web app with auth and AI-powered search under a tight 3-week after-hours timeline. It ships authentication scaffolding (Breeze/Fortify), Eloquent ORM for task persistence, and a convention-based structure that AI coding agents navigate fluently thanks to massive training-data coverage. The AI recall feature (FR-009/010) integrates via an LLM SDK querying against Eloquent-managed task history, keeping the architecture simple and the data grounded. Self-hosted deployment on a VPS with Docker keeps costs predictable and gives full control over the data layer — important given the privacy guardrail in the PRD.
