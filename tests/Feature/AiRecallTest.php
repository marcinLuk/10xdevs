<?php

use App\Models\Task;
use App\Models\User;
use App\Services\AiRecallResult;
use App\Services\AiRecallService;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function () {
    config(['prism.ai_recall.model' => 'anthropic/claude-sonnet-4.5']);
});

test('guest cannot access ai recall endpoint', function () {
    $this->post('/tasks/ask', ['question' => 'when did I water?'])
        ->assertRedirect('/login');
});

test('authenticated user with tasks gets a successful answer', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('You watered the tomatoes on 2026-05-20.'),
    ]);

    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'task_date' => '2026-05-20',
        'description' => 'Watered the tomatoes',
        'type' => 'watering',
    ]);

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => 'When did I water the tomatoes?'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'answer' => 'You watered the tomatoes on 2026-05-20.',
            'error' => null,
        ]);

    $fake->assertCallCount(1);
});

test('user with no tasks still gets a grounded response', function () {
    Prism::fake([
        TextResponseFake::make()->withText("I don't see any record of that in your log."),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => 'When did I plant tomatoes?'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'answer' => "I don't see any record of that in your log.",
        ]);
});

test('validation rejects empty question', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('question');
});

test('validation rejects too short question', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => 'hi'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('question');
});

test('validation rejects too long question', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => str_repeat('a', 501)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('question');
});

test('validation rejects missing question field', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('question');
});

test('ai service failure returns graceful error response', function () {
    $user = User::factory()->create();

    $this->instance(AiRecallService::class, new class extends AiRecallService
    {
        public function ask(User $user, string $question): AiRecallResult
        {
            return AiRecallResult::error('The AI service is temporarily unavailable. Please try again.');
        }
    });

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => 'When did I water?'])
        ->assertOk()
        ->assertJson([
            'ok' => false,
            'answer' => null,
            'error' => 'The AI service is temporarily unavailable. Please try again.',
        ]);
});

test('prompt contains only the authenticated users tasks', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('ok'),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Task::factory()->for($userA)->create([
        'description' => 'User A secret rosebush fertilization',
        'task_date' => '2026-05-15',
    ]);
    Task::factory()->for($userB)->create([
        'description' => 'User B sunflower watering',
        'task_date' => '2026-05-20',
    ]);

    $this->actingAs($userB)
        ->postJson('/tasks/ask', ['question' => 'What did I do recently?'])
        ->assertOk();

    $fake->assertRequest(function (array $recorded) {
        $request = $recorded[0];
        $systemPrompt = collect($request->systemPrompts())->map(fn ($p) => $p->content)->implode("\n");
        expect($systemPrompt)->toContain('User B sunflower watering');
        expect($systemPrompt)->not->toContain('User A secret rosebush fertilization');
    });
});

test('question input is trimmed and stripped of html tags', function () {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('ok'),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/tasks/ask', ['question' => '   <b>when did</b> I water?   '])
        ->assertOk();

    $fake->assertRequest(function (array $recorded) {
        $request = $recorded[0];
        expect($request->prompt())->toBe('when did I water?');
    });
});
