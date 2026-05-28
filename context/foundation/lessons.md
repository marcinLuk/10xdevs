# Lessons Learned

> Append-only register of recurring rules and patterns. Re-read at start by /10x-frame, /10x-research, /10x-plan, /10x-plan-review, /10x-implement, /10x-impl-review.

## Always branch + PR — no direct commits to master

- **Context**: Git workflow / all phases — any phase that involves branching, committing, or pushing code.
- **Problem**: Without a PR, changes bypass required reviews and CI checks, risking regressions in production.
- **Rule**: Always create a feature/fix branch and open a pull request. Direct commits to master are forbidden by branch protection.
- **Applies to**: all

## Always register new routes inside the existing auth middleware group

- **Context**: routes/web.php — adding new controller routes for authenticated features.
- **Problem**: Task routes were registered with inline ->middleware('auth') outside the existing Route::middleware('auth')->group(), creating inconsistency and risk of forgetting middleware on future routes.
- **Rule**: When adding new authenticated routes, place them inside the existing Route::middleware('auth')->group() block rather than using inline ->middleware() calls.
- **Applies to**: routes/web.php, any new route registration

## Always run tests and artisan commands via docker exec

- **Context**: Any test run command, php artisan call, or composer script in this project
- **Problem**: Host machine doesn't have composer/php in PATH or has the wrong version — commands fail with "command not found" or wrong PHP version errors
- **Rule**: Always run tests via docker exec, never via a bare composer/php command on the host.
- **Applies to**: all
