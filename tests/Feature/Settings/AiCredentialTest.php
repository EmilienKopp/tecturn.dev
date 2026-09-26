<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Support\Facades\DB;

test('the ai models settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('ai-credentials.index'))
        ->assertOk();
});

test('guests are redirected away from the ai models settings page', function () {
    $this->get(route('ai-credentials.index'))->assertRedirect();
});

test('a credential is stored with its key encrypted at rest', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-credentials.store'), [
            'label' => 'My Anthropic key',
            'driver' => 'anthropic',
            'model' => 'claude-sonnet-4-6',
            'api_key' => 'sk-secret-value',
        ])
        ->assertRedirect(route('ai-credentials.index'))
        ->assertSessionHasNoErrors();

    $credential = $user->aiCredentials()->sole();

    expect($credential->api_key)->toBe('sk-secret-value')
        ->and($credential->is_default)->toBeTrue();

    // The raw column must not hold the plaintext.
    $raw = DB::table('user_ai_credentials')->where('id', $credential->id)->value('api_key');
    expect($raw)->not->toContain('sk-secret-value');
});

test('the first credential is forced default and a later default demotes the previous', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('ai-credentials.store'), [
        'driver' => 'anthropic',
        'model' => 'claude-sonnet-4-6',
        'api_key' => 'key-one',
    ]);

    $this->actingAs($user)->post(route('ai-credentials.store'), [
        'driver' => 'openai',
        'model' => 'gpt-4.1',
        'api_key' => 'key-two',
        'is_default' => true,
    ]);

    $credentials = $user->aiCredentials()->orderBy('id')->get();

    expect($credentials)->toHaveCount(2)
        ->and($credentials[0]->is_default)->toBeFalse()
        ->and($credentials[1]->is_default)->toBeTrue()
        ->and($user->defaultAiCredential()->model)->toBe('gpt-4.1');
});

test('the openai-compatible driver requires a base url', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-credentials.store'), [
            'driver' => 'openai-compatible',
            'model' => 'fugu',
            'api_key' => 'key',
        ])
        ->assertSessionHasErrors('base_url');
});

test('non-compatible drivers require an api key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-credentials.store'), [
            'driver' => 'anthropic',
            'model' => 'claude-sonnet-4-6',
        ])
        ->assertSessionHasErrors('api_key');
});

test('an openai-compatible endpoint may be keyless', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-credentials.store'), [
            'driver' => 'openai-compatible',
            'model' => 'fugu',
            'base_url' => 'https://api.sakana.ai/v1',
        ])
        ->assertSessionHasNoErrors();

    expect($user->aiCredentials()->sole()->base_url)->toBe('https://api.sakana.ai/v1');
});

test('an unsupported driver is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-credentials.store'), [
            'driver' => 'not-a-provider',
            'model' => 'whatever',
            'api_key' => 'key',
        ])
        ->assertSessionHasErrors('driver');
});

test('a user can switch their default credential', function () {
    $user = User::factory()->create();
    $first = UserAiCredential::factory()->for($user)->default()->create();
    $second = UserAiCredential::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('ai-credentials.default', $second))
        ->assertRedirect(route('ai-credentials.index'));

    expect($first->refresh()->is_default)->toBeFalse()
        ->and($second->refresh()->is_default)->toBeTrue();
});

test('deleting the default promotes another credential', function () {
    $user = User::factory()->create();
    UserAiCredential::factory()->for($user)->create(); // older
    $default = UserAiCredential::factory()->for($user)->default()->create();

    $this->actingAs($user)
        ->delete(route('ai-credentials.destroy', $default))
        ->assertRedirect(route('ai-credentials.index'));

    expect($user->aiCredentials()->where('is_default', true)->count())->toBe(1);
});

test('a user cannot delete another users credential', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $credential = UserAiCredential::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('ai-credentials.destroy', $credential))
        ->assertForbidden();

    $this->assertDatabaseHas('user_ai_credentials', ['id' => $credential->id]);
});
