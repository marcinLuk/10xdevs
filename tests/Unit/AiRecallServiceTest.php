<?php

use App\Models\Task;
use App\Models\User;
use App\Services\AiRecallResult;
use App\Services\AiRecallService;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

test('service fetches only the authenticated users tasks', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('ok'),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Task::factory()->for($userA)->create(['description' => 'User A task', 'task_date' => '2026-05-10']);
    Task::factory()->for($userB)->create(['description' => 'User B task', 'task_date' => '2026-05-15']);

    app(AiRecallService::class)->ask($userB, 'What did I do?');

    $fake->assertRequest(function (array $recorded) {
        $systemPrompt = collect($recorded[0]->systemPrompts())->map(fn ($p) => $p->content)->implode("\n");
        expect($systemPrompt)->toContain('User B task');
        expect($systemPrompt)->not->toContain('User A task');
    });
});

test('task limit from config is respected', function () {
    config(['prism.ai_recall.task_limit' => 3]);

    $fake = Prism::fake([
        TextResponseFake::make()->withText('ok'),
    ]);

    $user = User::factory()->create();
    Task::factory()->for($user)->count(10)->sequence(
        ['task_date' => '2026-05-01', 'description' => 'old-1'],
        ['task_date' => '2026-05-02', 'description' => 'old-2'],
        ['task_date' => '2026-05-03', 'description' => 'old-3'],
        ['task_date' => '2026-05-04', 'description' => 'old-4'],
        ['task_date' => '2026-05-05', 'description' => 'old-5'],
        ['task_date' => '2026-05-06', 'description' => 'old-6'],
        ['task_date' => '2026-05-07', 'description' => 'old-7'],
        ['task_date' => '2026-05-20', 'description' => 'newest-A'],
        ['task_date' => '2026-05-21', 'description' => 'newest-B'],
        ['task_date' => '2026-05-22', 'description' => 'newest-C'],
    )->create();

    app(AiRecallService::class)->ask($user, 'recent?');

    $fake->assertRequest(function (array $recorded) {
        $systemPrompt = collect($recorded[0]->systemPrompts())->map(fn ($p) => $p->content)->implode("\n");
        // Newest three (May 22, 21, 20) must be present
        expect($systemPrompt)->toContain('newest-A');
        expect($systemPrompt)->toContain('newest-B');
        expect($systemPrompt)->toContain('newest-C');
        // Older entries beyond limit must NOT be present
        expect($systemPrompt)->not->toContain('old-1');
        expect($systemPrompt)->not->toContain('old-7');
    });
});

test('tasks are ordered newest first in prompt', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('ok'),
    ]);

    $user = User::factory()->create();
    Task::factory()->for($user)->create(['task_date' => '2026-05-01', 'description' => 'oldest-entry']);
    Task::factory()->for($user)->create(['task_date' => '2026-05-15', 'description' => 'middle-entry']);
    Task::factory()->for($user)->create(['task_date' => '2026-05-25', 'description' => 'newest-entry']);

    app(AiRecallService::class)->ask($user, 'tell me');

    $fake->assertRequest(function (array $recorded) {
        $systemPrompt = collect($recorded[0]->systemPrompts())->map(fn ($p) => $p->content)->implode("\n");
        $newestPos = strpos($systemPrompt, 'newest-entry');
        $middlePos = strpos($systemPrompt, 'middle-entry');
        $oldestPos = strpos($systemPrompt, 'oldest-entry');
        expect($newestPos)->toBeLessThan($middlePos);
        expect($middlePos)->toBeLessThan($oldestPos);
    });
});

test('AiRecallResult success exposes answer and ok=true', function () {
    $result = AiRecallResult::success('hello');

    expect($result->ok)->toBeTrue();
    expect($result->answer)->toBe('hello');
    expect($result->error)->toBeNull();
});

test('AiRecallResult error exposes error and ok=false', function () {
    $result = AiRecallResult::error('boom');

    expect($result->ok)->toBeFalse();
    expect($result->answer)->toBeNull();
    expect($result->error)->toBe('boom');
});

test('service returns success result with text from Prism', function () {
    Prism::fake([
        TextResponseFake::make()->withText('the answer'),
    ]);

    $user = User::factory()->create();
    Task::factory()->for($user)->create();

    $result = app(AiRecallService::class)->ask($user, 'a question');

    expect($result->ok)->toBeTrue();
    expect($result->answer)->toBe('the answer');
});
