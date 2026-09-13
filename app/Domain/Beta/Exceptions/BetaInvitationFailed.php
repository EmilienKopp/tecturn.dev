<?php

declare(strict_types=1);

namespace App\Domain\Beta\Exceptions;

use Inertia\Inertia;
use RuntimeException;

class BetaInvitationFailed extends RuntimeException
{
    public function render()
    {
        Inertia::flash('error', 'Beta invitation failed.');

        return Inertia::back();
    }
}
