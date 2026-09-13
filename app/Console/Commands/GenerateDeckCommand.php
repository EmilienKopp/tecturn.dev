<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Models\Team;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deck:generate {team : Team slug or id} {--file= : Path to a markdown plan file (omit to read from stdin)} {--name= : Deck name (defaults to the generated title)}')]
#[Description('Generate a Tecturn deck from a markdown plan using the DeckArchitect agent.')]
class GenerateDeckCommand extends Command
{
    public function handle(GenerateDeckFromPlan $generateDeck): int
    {
        $team = $this->resolveTeam((string) $this->argument('team'));

        if ($team === null) {
            $this->error('Team not found.');

            return self::FAILURE;
        }

        $plan = $this->readPlan();

        if ($plan === '') {
            $this->error('The plan is empty. Pass --file or pipe markdown via stdin.');

            return self::FAILURE;
        }

        $this->info('Asking the DeckArchitect to build your deck…');

        $presentation = $generateDeck->execute(
            new GenerateDeckFromPlanCommand(
                team_id: $team->id,
                name: (string) ($this->option('name') ?? ''),
                plan: $plan,
            ),
        );

        $this->info("Created \"{$presentation->name}\" with ".count($presentation->content->slides).' slides.');
        $this->line(route('presentations.edit', [
            'current_team' => $team->slug,
            'presentation' => $presentation->id,
        ]));

        return self::SUCCESS;
    }

    private function resolveTeam(string $identifier): ?Team
    {
        return Team::firstWhere('slug', $identifier)
            ?? (is_numeric($identifier) ? Team::find((int) $identifier) : null);
    }

    private function readPlan(): string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            if (! is_file($file)) {
                $this->error("File not found: {$file}");

                return '';
            }

            return trim((string) file_get_contents($file));
        }

        return trim((string) file_get_contents('php://stdin'));
    }
}
