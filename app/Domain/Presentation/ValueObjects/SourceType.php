<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

/**
 * Where a presentation's slides come from. `Editor` decks are built in the
 * Tecturn editor and rendered from stored content + flow. External decks bring
 * their own slides (an uploaded PDF or an embedded Google Slides deck) and only
 * reuse the live layer (dock, translation, footer).
 */
enum SourceType: string
{
    case Editor = 'editor';
    case Pdf = 'pdf';
    case GoogleSlides = 'google_slides';

    public function isExternal(): bool
    {
        return $this !== self::Editor;
    }
}
