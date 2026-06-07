<?php

use App\Models\Task;
use App\Models\User;

/*
| These tests render the garden-recall prompt the same way AiRecallService does
| (view('prompts.garden-recall', ['tasks' => ...])->render()), so the Blade
| template — the single point where the grounding contract can silently change —
| is the assertion target. No Prism call, no API, no cost.
|
| Oracle is the PRD guardrail ("AI must never return a date or event not present
| in the gardener's task history") and the test-plan Risk #1 contract, NOT the
| template's current wording. We assert on a small set of stable,
| intention-revealing substrings rather than a whole-template snapshot that would
| break on cosmetic edits.
*/

test('garden recall prompt carries the grounding contract', function () {
    // The grounding rules are static regardless of task data, so an empty log is enough.
    $prompt = view('prompts.garden-recall', ['tasks' => collect()])->render();

    // Anti-fabrication: the model must never invent data not in the log.
    expect($prompt)->toContain('NEVER invent');
    // Explicit-not-found: on no match the model must say so rather than guess.
    expect($prompt)->toContain('say so explicitly');
    // ISO-date instruction: the model must quote real dates in YYYY-MM-DD form.
    expect($prompt)->toContain('YYYY-MM-DD');
    // Only-this-data: the log is the entire source of truth.
    expect($prompt)->toContain('source of truth');
});

test('garden recall prompt renders a real task date in iso form', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'task_date' => '2026-05-20',
        'description' => 'Watered the tomatoes',
        'type' => 'watering',
    ]);

    $tasks = $user->tasks()->orderBy('task_date', 'desc')->get();

    $prompt = view('prompts.garden-recall', ['tasks' => $tasks])->render();

    // Guards against a future change to the date="…" formatting in the template:
    // the seeded task's date must appear as an ISO YYYY-MM-DD string.
    expect($prompt)->toContain('2026-05-20');
});
