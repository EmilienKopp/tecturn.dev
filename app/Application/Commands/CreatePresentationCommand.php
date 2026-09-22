<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class CreatePresentationCommand
{
    public function __construct(
        public int $team_id,
        public string $name,
        /** Default background (hex or gradient) for the first slide, from the creator's branding. */
        public ?string $slide_background = null,
        /** One of the SourceType values: editor (default), pdf, google_slides. */
        public string $sourceType = 'editor',
        /** Published Google Slides URL — only for google_slides decks. */
        public ?string $externalUrl = null,
        /** Absolute path to the uploaded PDF on disk — only for pdf decks. */
        public ?string $pdfFilePath = null,
        /** Original file name for the uploaded PDF. */
        public ?string $pdfFileName = null,
    ) {}
}
