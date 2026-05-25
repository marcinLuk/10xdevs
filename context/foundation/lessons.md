# Lessons Learned

> Append-only register of recurring rules and patterns. Re-read at start by /10x-frame, /10x-research, /10x-plan, /10x-plan-review, /10x-implement, /10x-impl-review.

## Always branch + PR — no direct commits to master

- **Context**: Git workflow / all phases — any phase that involves branching, committing, or pushing code.
- **Problem**: Without a PR, changes bypass required reviews and CI checks, risking regressions in production.
- **Rule**: Always create a feature/fix branch and open a pull request. Direct commits to master are forbidden by branch protection.
- **Applies to**: all
