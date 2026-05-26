<?php

use App\Models\User;

it('redirects guests from dashboard to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('renders dashboard for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSeeText($user->name);
});
