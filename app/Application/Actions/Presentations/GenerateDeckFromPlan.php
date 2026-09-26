<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Ai\Agents\Deckster;
use App\Ai\AiCredentialResolver;
use App\Ai\DeckAssembler;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

/**
 * Fills a requested draft with an AI-built deck: runs Deckster against the
 * draft's stored plan, assembles the result, and marks the draft complete.
 */
class GenerateDeckFromPlan
{
    public function __construct(
        private readonly PresentationRepository $presentations,
        private readonly DeckAssembler $assembler,
        private readonly AiCredentialResolver $credentials,
    ) {}

    public function execute(GenerateDeckFromPlanCommand $command): PresentationEntity
    {
        $presentation = $this->presentations->findById($command->presentation_id);

        $plan = $presentation->draftPlan;

        if ($plan === null || $plan === '') {
            throw new RuntimeException("Presentation {$command->presentation_id} has no draft plan to build from.");
        }

        $structured = $this->runDeckster($plan, $command->ai_credential_id);

        $deck = $this->assembler->assemble($structured, $command->branding);

        $title = $structured['title'] ?? null;
        $name = $command->name !== ''
            ? $command->name
            : (is_string($title) && $title !== '' ? $title : 'Untitled deck');

        $presentation->rename($name);
        $presentation->replaceContent($deck['content']);
        $presentation->replaceFlow($deck['flow']);
        $presentation->markDraftCompleted(now()->toDateTimeImmutable());

        $saved = $this->presentations->save($presentation);
        $saved->onCreated();

        return $saved;
    }

    /**
     * Run Deckster against the plan, using the user's own AI credential when one
     * is set (bring your own AI) and falling back to the agent's house provider
     * otherwise. A credential that has since been deleted also falls back.
     *
     * @return array<string, mixed>
     */
    private function runDeckster(string $plan, ?int $credentialId): array
    {
        $resolved = $credentialId === null
            ? null
            : $this->credentials->resolve($credentialId);

        if ($resolved === null) {
            $response = (new Deckster)->prompt($plan);
        } else {
            ['provider' => $provider, 'model' => $model] = $this->credentials->apply($resolved);
            $response = (new Deckster)->prompt($plan, provider: $provider, model: $model);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Deckster returned a non-structured response.');
        }

        return $response->toArray();
    }
}
