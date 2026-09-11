<?php

declare(strict_types=1);

use App\Ai\Agents\DeckArchitect;
use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Models\Team;

function fakeDeck(): array
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
            [
                'layout' => 'left-right',
                'title' => 'Before and after',
                'blocks' => [
                    ['slot' => 'left', 'type' => 'text', 'content' => 'The problem'],
                    ['slot' => 'right', 'type' => 'code', 'content' => 'widget()', 'lang' => 'php', 'revealStep' => 1],
                ],
            ],
        ],
    ];
}

it('generates and persists a deck from a plan via the action', function () {
    DeckArchitect::fake([fakeDeck()]);

    $team = Team::factory()->create();

    $entity = app(GenerateDeckFromPlan::class)->execute(
        new GenerateDeckFromPlanCommand(team_id: $team->id, name: '', plan: '# My plan'),
    );

    expect($entity->id)->not->toBeNull()
        ->and($entity->name)->toBe('Intro to Widgets') // falls back to the generated title
        ->and($entity->content->slides)->toHaveCount(2)
        ->and($entity->flow)->not->toBeNull();

    $this->assertDatabaseHas('presentations', [
        'id' => $entity->id,
        'team_id' => $team->id,
        'name' => 'Intro to Widgets',
    ]);

    DeckArchitect::assertPrompted('# My plan');
});

it('prefers an explicit name over the generated title', function () {
    DeckArchitect::fake([fakeDeck()]);

    $team = Team::factory()->create();

    $entity = app(GenerateDeckFromPlan::class)->execute(
        new GenerateDeckFromPlanCommand(team_id: $team->id, name: 'Custom name', plan: '# plan'),
    );

    expect($entity->name)->toBe('Custom name');
});

it('generates a deck through the artisan command from a file', function () {
    DeckArchitect::fake([fakeDeck()]);

    $team = Team::factory()->create();

    $path = tempnam(sys_get_temp_dir(), 'plan').'.md';
    file_put_contents($path, '# My talk plan');

    $this->artisan('deck:generate', ['team' => $team->slug, '--file' => $path])
        ->assertSuccessful();

    unlink($path);

    $this->assertDatabaseHas('presentations', [
        'team_id' => $team->id,
        'name' => 'Intro to Widgets',
    ]);
});

it('fails cleanly for an unknown team', function () {
    DeckArchitect::fake([fakeDeck()]);

    $path = tempnam(sys_get_temp_dir(), 'plan').'.md';
    file_put_contents($path, '# plan');

    $this->artisan('deck:generate', ['team' => 'nope-nope', '--file' => $path])
        ->assertFailed();

    unlink($path);
});
