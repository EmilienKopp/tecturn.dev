<?php

declare(strict_types=1);

namespace App\Presentation;

enum ExportFormat: string
{
    case SvelteSource = 'svelte';
    case WebComponent = 'web-component';
    case JSON = 'json';

    public function mimeType(): string
    {
        return match ($this) {
            self::SvelteSource => 'text/plain',
            self::WebComponent => 'text/javascript',
            self::JSON => 'application/json',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::SvelteSource => 'svelte',
            self::WebComponent => 'js',
            self::JSON => 'json',
        };
    }
}
