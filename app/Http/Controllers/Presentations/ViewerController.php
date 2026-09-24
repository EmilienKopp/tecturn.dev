<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Models\Presentation;
use Inertia\Inertia;
use Inertia\Response;

class ViewerController extends Controller
{
    public function __invoke(Presentation $presentation): Response
    {
        return Inertia::render('presentations/Viewer', [
            'presentationName' => $presentation->name,
            'embedToken' => $presentation->embed_token,
        ]);
    }
}
