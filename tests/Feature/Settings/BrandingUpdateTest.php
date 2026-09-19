<?php

use App\Models\User;
use App\Support\Branding;

test('a new user gets the default branding palette', function () {
    $user = User::factory()->create();

    expect($user->branding)->toBe(Branding::DEFAULTS);
});

test('the branding settings page is displayed', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('branding.edit'))
        ->assertOk();
});

test('guests are redirected away from the branding settings page', function () {
    $this
        ->get(route('branding.edit'))
        ->assertRedirect();
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
        ->assertRedirect(route('branding.edit'));

    expect($user->refresh()->branding)->toBe([
        ...$palette,
        'fontFamily' => null,
        'fontSize' => null,
        'fontWeight' => null,
    ]);
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

test('the background slot accepts a linear gradient', function () {
    $user = User::factory()->create();

    $gradient = 'linear-gradient(135deg, #0f2027, #2c5364)';

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [...Branding::DEFAULTS, 'background' => $gradient],
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->branding['background'])->toBe($gradient);
});

test('only the background slot accepts a gradient', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [
                ...Branding::DEFAULTS,
                'primary' => 'linear-gradient(135deg, #0f2027, #2c5364)',
            ],
        ])
        ->assertSessionHasErrors('branding.primary');
});

test('malformed gradients are rejected for the background slot', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [
                ...Branding::DEFAULTS,
                'background' => 'linear-gradient(135deg, url(evil), #2c5364)',
            ],
        ])
        ->assertSessionHasErrors('branding.background');
});

test('branding typography defaults can be saved and cleared', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [
                ...Branding::DEFAULTS,
                'fontFamily' => 'Lora',
                'fontSize' => '2rem',
                'fontWeight' => 'semibold',
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->branding)
        ->fontFamily->toBe('Lora')
        ->fontSize->toBe('2rem')
        ->fontWeight->toBe('semibold');

    $this
        ->actingAs($user)
        ->patch('/settings/branding', ['branding' => [...Branding::DEFAULTS]])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->branding['fontFamily'])->toBeNull();
});

test('branding typography rejects unknown weights and malformed sizes', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/settings/branding', [
            'branding' => [
                ...Branding::DEFAULTS,
                'fontSize' => 'huge',
                'fontWeight' => '900',
            ],
        ])
        ->assertSessionHasErrors(['branding.fontSize', 'branding.fontWeight']);
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
