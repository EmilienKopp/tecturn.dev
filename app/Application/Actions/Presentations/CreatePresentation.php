<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\CreatePresentationCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Domain\Presentation\ValueObjects\PresentationSource;
use App\Domain\Presentation\ValueObjects\SourceType;

class CreatePresentation
{
    public function __construct(
        private readonly PresentationRepository $presentations,
    ) {}

    public function execute(CreatePresentationCommand $command): PresentationEntity
    {
        $type = SourceType::from($command->sourceType);

        $presentation = new PresentationEntity(
            team_id: $command->team_id,
            name: $command->name,
            content: PresentationContent::empty($command->slide_background),
            source: new PresentationSource(
                type: $type,
                externalUrl: $type === SourceType::GoogleSlides ? $command->externalUrl : null,
            ),
        );

        $saved = $this->presentations->save($presentation);

        // The PDF lives in a media collection, so it's attached after the row
        // exists and its id is known.
        if ($command->pdfFilePath !== null) {
            $this->presentations->storeSourcePdf(
                $saved->id,
                $command->pdfFilePath,
                $command->pdfFileName ?? 'source.pdf',
            );
        }

        return $saved;
    }
}
