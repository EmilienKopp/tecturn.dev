<?php

declare(strict_types=1);

use App\Ai\Agents\Deckster;
use App\Models\PresentationModel;
use App\Models\User;

function fakeHttpDeck(): array
{
    return [
        'title' => 'Intro to Widgets',
        'slides' => [
            [
                'layout' => 'center',
                'title' => 'Title',
                'blocks' => [
                    ['slot' => 'main', 'type' => 'text', 'content' => 'Intro to Widgets'],
                ],
            ],
        ],
    ];
}

test('a magic draft is generated from a plan and redirects to the editor', function () {
    Deckster::fake([fakeHttpDeck()]);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '# My talk plan',
        ]);

    $presentation = PresentationModel::query()->firstOrFail();

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $team->slug,
        'presentation' => $presentation->id,
    ]));

    expect($presentation->team_id)->toBe($team->id)
        ->and($presentation->name)->toBe('Intro to Widgets');

    Deckster::assertPrompted('# My talk plan');
});

test('an explicit name overrides the generated title', function () {
    Deckster::fake([fakeHttpDeck()]);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '# plan',
            'name' => 'Custom name',
        ]);

    expect(PresentationModel::query()->firstOrFail()->name)->toBe('Custom name');
});

test('the plan is required', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '',
        ]);

    $response->assertSessionHasErrors('plan');
});

test('a failed generation surfaces a validation error and creates nothing', function () {
    Deckster::fake([function () {
        throw new RuntimeException('provider exploded');
    }]);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '# plan',
        ]);

    $response->assertSessionHasErrors('plan');

    expect(PresentationModel::query()->count())->toBe(0);
});

test('guests cannot generate a draft', function () {
    $user = User::factory()->create();

    $response = $this->post(route('presentations.generate', ['current_team' => $user->currentTeam->slug]), [
        'plan' => '# plan',
    ]);

    $response->assertRedirect(route('login'));
});
