<?php

declare(strict_types=1);

namespace App\Http\Controllers\Beta;

use App\Http\Controllers\Controller;
use App\Support\Features;
use Inertia\Inertia;
use Inertia\Response;

class ShowBetaRegistrationController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(Features::registration()->allowsBetaRequests(), 404);

        return Inertia::render('beta/register');
    }
}
