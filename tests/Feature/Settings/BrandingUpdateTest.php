<?php

use App\Models\User;
use App\Support\Branding;

test('a new user gets the default branding palette', function () {
    $user = User::factory()->create();

    expect($user->branding)->toBe(Branding::DEFAULTS);
});

test('branding can be updated with a full palette', function () {
    $user = User::factory()->create();

    $palette = [
        'background' => '#0f172a',
        'primary' => '#3b82f6',
        'secondary' => '#94a3b8',
        'accent' => '#eab308',
        'success' => '#22c55e',
        'danger' => '#ef4444',
    ];

    $response = $this
        ->actingAs($user)
        ->patch('/settings/branding', ['branding' => $palette]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->branding)->toBe($palette);
});

test('branding colors are normalized to lowercase hex', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [...Branding::DEFAULTS, 'primary' => '#ABCDEF'],
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->branding['primary'])->toBe('#abcdef');
});

test('branding rejects non-hex color values', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [...Branding::DEFAULTS, 'danger' => 'red'],
        ])
        ->assertSessionHasErrors('branding.danger');
});

test('branding requires all six slots', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => ['primary' => '#123456'],
        ])
        ->assertSessionHasErrors(['branding.background', 'branding.danger']);
});
