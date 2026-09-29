<?php

declare(strict_types=1);

use App\Events\Presentations\MessageSent;
use App\Events\Presentations\ReactionSent;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

test('the viewer page renders for any visitor using the embed token', function () {
    $presentation = Presentation::factory()->create();

    $response = $this->get(
        route('presentations.viewer', ['presentation' => $presentation->embed_token]),
    );

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Viewer')
        ->where('presentationName', $presentation->name)
        ->where('embedToken', $presentation->embed_token),
    );
});

test('sending a valid reaction dispatches a ReactionSent event', function () {
    Event::fake([ReactionSent::class]);

    $presentation = Presentation::factory()->create();

    $response = $this->postJson(
        route('presentations.reactions', ['presentation' => $presentation->embed_token]),
        ['emoji' => '👏'],
    );

    $response->assertNoContent();

    Event::assertDispatched(ReactionSent::class, function (ReactionSent $event) use ($presentation) {
        return $event->embedToken === $presentation->embed_token && $event->emoji === '👏';
    });
});

test('sending an unsupported emoji is rejected', function () {
    $presentation = Presentation::factory()->create();

    $response = $this->postJson(
        route('presentations.reactions', ['presentation' => $presentation->embed_token]),
        ['emoji' => '💩'],
    );

    $response->assertUnprocessable();
});

test('the viewer receives the default reaction set', function () {
    $presentation = Presentation::factory()->create();

    $this->get(route('presentations.viewer', ['presentation' => $presentation->embed_token]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reactions', ['👏', '❤️', '😂', '🤯', '🙌', '🔥']),
        );
});

test('a custom reaction set gates what the viewer sees and can send', function () {
    Event::fake([ReactionSent::class]);

    $presentation = Presentation::factory()->create([
        'talk_settings' => ['reactions' => ['🦄', '🎉']],
    ]);

    $this->get(route('presentations.viewer', ['presentation' => $presentation->embed_token]))
        ->assertInertia(fn (Assert $page) => $page->where('reactions', ['🦄', '🎉']));

    // A custom emoji is accepted.
    $this->postJson(
        route('presentations.reactions', ['presentation' => $presentation->embed_token]),
        ['emoji' => '🦄'],
    )->assertNoContent();

    // A default that is not in the custom set is now rejected.
    $this->postJson(
        route('presentations.reactions', ['presentation' => $presentation->embed_token]),
        ['emoji' => '👏'],
    )->assertUnprocessable();
});

test('a free-text message broadcasts when the presentation allows it', function () {
    Event::fake([MessageSent::class]);

    $presentation = Presentation::factory()->create([
        'talk_settings' => ['allowFreeText' => true, 'freeTextMaxLength' => 20],
    ]);

    $this->postJson(
        route('presentations.messages', ['presentation' => $presentation->embed_token]),
        ['message' => 'Hello there'],
    )->assertNoContent();

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($presentation) {
        return $event->embedToken === $presentation->embed_token && $event->message === 'Hello there';
    });
});

test('free-text messages are forbidden when the presentation disallows them', function () {
    Event::fake([MessageSent::class]);

    $presentation = Presentation::factory()->create([
        'talk_settings' => ['allowFreeText' => false],
    ]);

    $this->postJson(
        route('presentations.messages', ['presentation' => $presentation->embed_token]),
        ['message' => 'Hello'],
    )->assertForbidden();

    Event::assertNotDispatched(MessageSent::class);
});

test('a free-text message longer than the configured max is rejected', function () {
    $presentation = Presentation::factory()->create([
        'talk_settings' => ['allowFreeText' => true, 'freeTextMaxLength' => 10],
    ]);

    $this->postJson(
        route('presentations.messages', ['presentation' => $presentation->embed_token]),
        ['message' => str_repeat('a', 11)],
    )->assertUnprocessable();
});

test('talk settings can be saved via the update route', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->put(route('presentations.update', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $presentation->id,
        ]), [
            'talk_settings' => [
                'showReactions' => true,
                'showDock' => false,
                'showTranslation' => false,
                'timerMode' => 'countdown',
                'durationMinutes' => 20,
                'reactions' => ['🦄', '🎉', '🚀'],
            ],
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $stored = Presentation::findOrFail($presentation->id);
    expect($stored->talk_settings['showReactions'])->toBeTrue()
        ->and($stored->talk_settings['showDock'])->toBeFalse()
        ->and($stored->talk_settings['showTranslation'])->toBeFalse()
        ->and($stored->talk_settings['timerMode'])->toBe('countdown')
        ->and($stored->talk_settings['durationMinutes'])->toBe(20)
        ->and($stored->talk_settings['reactions'])->toBe(['🦄', '🎉', '🚀']);
});

test('customising more than the maximum reactions is rejected', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->put(route('presentations.update', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $presentation->id,
        ]), [
            'talk_settings' => [
                'reactions' => array_map(fn (int $i): string => "e{$i}", range(1, 11)),
            ],
        ]);

    $response->assertSessionHasErrors('talk_settings.reactions');
});
