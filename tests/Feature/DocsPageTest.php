<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the documentation page renders for guests', function () {
    $this->get(route('docs'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('docs/Index'));
});

test('the documentation page renders for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('docs'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('docs/Index'));
});

test('the sidebar navigation links to the documentation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard', $user->currentTeam->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigation.0.children.3.title', 'Documentation')
            ->where('navigation.0.children.3.url', route('docs')),
        );
});
