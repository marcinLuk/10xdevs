<?php

use App\Models\Task;
use App\Models\User;

test('guest cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('guest cannot create a task', function () {
    $this->post('/tasks', [
        'description' => 'Water the roses',
        'task_date' => now()->format('Y-m-d'),
    ])->assertRedirect('/login');
});

test('authenticated user sees empty state when no tasks exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No tasks yet');
});

test('authenticated user can create a task with all fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => 'Watered the tomatoes',
            'task_date' => now()->format('Y-m-d'),
            'type_choice' => 'watering',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'description' => 'Watered the tomatoes',
        'type' => 'watering',
    ]);
});

test('authenticated user can create a task without type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => 'General garden maintenance',
            'task_date' => now()->format('Y-m-d'),
            'type_choice' => '',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'description' => 'General garden maintenance',
        'type' => null,
    ]);
});

test('authenticated user can create a task with custom type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => 'Pruned the hedge',
            'task_date' => now()->format('Y-m-d'),
            'type_choice' => '__custom__',
            'custom_type' => 'pruning',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'description' => 'Pruned the hedge',
        'type' => 'pruning',
    ]);
});

test('task creation validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => '',
            'task_date' => '',
        ])
        ->assertSessionHasErrors(['description', 'task_date']);
});

test('task creation rejects future dates', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => 'Future task',
            'task_date' => now()->addDay()->format('Y-m-d'),
            'type_choice' => '',
        ])
        ->assertSessionHasErrors('task_date');
});

test('task creation rejects overly long description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => str_repeat('a', 501),
            'task_date' => now()->format('Y-m-d'),
            'type_choice' => '',
        ])
        ->assertSessionHasErrors('description');
});

test('tasks appear in reverse chronological order', function () {
    $user = User::factory()->create();

    Task::factory()->for($user)->create(['task_date' => now()->subDays(2), 'description' => 'Older task']);
    Task::factory()->for($user)->create(['task_date' => now(), 'description' => 'Newest task']);
    Task::factory()->for($user)->create(['task_date' => now()->subDay(), 'description' => 'Middle task']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSeeInOrder(['Newest task', 'Middle task', 'Older task']);
});

test('pagination works with more than 15 tasks', function () {
    $user = User::factory()->create();

    Task::factory()->for($user)->count(20)->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();

    $response2 = $this->actingAs($user)->get('/dashboard?page=2');
    $response2->assertOk();
});

test('user cannot see another users tasks', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Task::factory()->for($user1)->create(['description' => 'User1 secret task']);

    $this->actingAs($user2)
        ->get('/dashboard')
        ->assertDontSee('User1 secret task');
});

test('flash message appears after successful task creation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'description' => 'Planted sunflowers',
            'task_date' => now()->format('Y-m-d'),
            'type_choice' => 'planting',
        ])
        ->assertSessionHas('success', 'Task added successfully.');
});
