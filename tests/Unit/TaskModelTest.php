<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

test('task belongs to a user', function () {
    $task = Task::factory()->create();

    expect($task->user)->toBeInstanceOf(User::class);
});

test('user has many tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(3)->create();

    expect($user->tasks)->toHaveCount(3);
});

test('task_date is cast to a Carbon date', function () {
    $task = Task::factory()->create(['task_date' => '2026-03-15']);

    expect($task->task_date)->toBeInstanceOf(Carbon::class);
    expect($task->task_date->format('Y-m-d'))->toBe('2026-03-15');
});

test('scopeForUser filters tasks by user_id', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Task::factory()->for($user1)->count(2)->create();
    Task::factory()->for($user2)->count(3)->create();

    expect(Task::forUser($user1)->count())->toBe(2);
    expect(Task::forUser($user2)->count())->toBe(3);
});
