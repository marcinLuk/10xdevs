<?php

use App\Models\Task;
use App\Models\User;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

/*
| The eval route runs a REAL OpenRouter call in production use. In the suite we
| Prism::fake it so these tests never hit the API — they assert the route's
| guard (env + token), its response shape, and the rollback guarantee, NOT the
| model's grounding (that is the promptfoo eval's job in Phase 3).
|
| The route is registered only under local|testing; the test env is `testing`,
| so it is reachable here.
*/

beforeEach(function () {
    config(['services.eval.token' => 'test-eval-secret']);
});

test('eval grounding route returns 200 and answer with a valid token', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('You watered the tomatoes on 2026-05-20.'),
    ]);

    $this->withHeader('X-Eval-Token', 'test-eval-secret')
        ->postJson('/eval/grounding', [
            'question' => 'When did I water the tomatoes?',
            'tasks' => [
                ['task_date' => '2026-05-20', 'type' => 'watering', 'description' => 'Watered the tomatoes'],
            ],
        ])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'answer' => 'You watered the tomatoes on 2026-05-20.',
        ]);

    $fake->assertCallCount(1);
});

test('eval grounding route rejects a wrong token with 403', function () {
    Prism::fake([TextResponseFake::make()->withText('should not be called')]);

    $this->withHeader('X-Eval-Token', 'wrong-secret')
        ->postJson('/eval/grounding', [
            'question' => 'When did I water the tomatoes?',
            'tasks' => [],
        ])
        ->assertStatus(403);
});

test('eval grounding route rejects a missing token with 403', function () {
    Prism::fake([TextResponseFake::make()->withText('should not be called')]);

    $this->postJson('/eval/grounding', [
        'question' => 'When did I water the tomatoes?',
        'tasks' => [],
    ])->assertStatus(403);
});

test('eval grounding route rejects any token when none is configured', function () {
    config(['services.eval.token' => null]);

    $this->withHeader('X-Eval-Token', '')
        ->postJson('/eval/grounding', ['question' => 'q', 'tasks' => []])
        ->assertStatus(403);
});

test('eval grounding route persists no user or task rows after the request', function () {
    Prism::fake([
        TextResponseFake::make()->withText('grounded answer'),
    ]);

    $this->withHeader('X-Eval-Token', 'test-eval-secret')
        ->postJson('/eval/grounding', [
            'question' => 'What did I do?',
            'tasks' => [
                ['task_date' => '2026-05-20', 'type' => 'watering', 'description' => 'EVAL-ROLLBACK-MARKER watering'],
            ],
        ])
        ->assertOk();

    // The transient user + task the controller seeded must be rolled back.
    expect(User::count())->toBe(0);
    expect(Task::count())->toBe(0);
    expect(Task::where('description', 'EVAL-ROLLBACK-MARKER watering')->exists())->toBeFalse();
});
