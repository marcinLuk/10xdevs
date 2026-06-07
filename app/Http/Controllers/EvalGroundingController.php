<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\AiRecallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Hermetic eval endpoint that lets promptfoo drive the REAL prompt assembly +
 * AiRecallService against the live model, per case.
 *
 * Safety: this controller is only reachable when its route is registered, and
 * routes/web.php registers it ONLY under APP_ENV local|testing (see the
 * env-guarded block). It additionally requires a matching X-Eval-Token header.
 * Every seeded User/Task is created inside a transaction that is ALWAYS rolled
 * back, so no eval data persists. It runs a real OpenRouter call — that is the
 * point of the eval.
 */
class EvalGroundingController extends Controller
{
    public function handle(Request $request, AiRecallService $service): JsonResponse
    {
        $expected = (string) config('services.eval.token');
        $provided = (string) $request->header('X-Eval-Token', '');

        // Constant-time comparison; an unset/empty token can never match.
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => ['required', 'string'],
            'tasks' => ['present', 'array'],
            'tasks.*.task_date' => ['required', 'string'],
            'tasks.*.description' => ['required', 'string'],
            'tasks.*.type' => ['nullable', 'string'],
        ]);

        $answer = null;
        $ok = false;

        // Seed a throwaway user + the case's tasks, run the real service, capture
        // the answer, then roll everything back. The finally guarantees rollback
        // even if the service throws, so eval data never persists.
        DB::beginTransaction();
        try {
            $user = User::factory()->create();

            foreach ($validated['tasks'] as $task) {
                Task::factory()->for($user)->create([
                    'task_date' => $task['task_date'],
                    'description' => $task['description'],
                    'type' => $task['type'] ?? null,
                ]);
            }

            $result = $service->ask($user, $validated['question']);
            $answer = $result->answer;
            $ok = $result->ok;
        } finally {
            DB::rollBack();
        }

        return response()->json([
            'answer' => $answer,
            'ok' => $ok,
        ]);
    }
}
