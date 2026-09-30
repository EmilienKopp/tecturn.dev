<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Models\Presentation;
use Inertia\Inertia;
use Inertia\Response;

class RemoteControlController extends Controller
{
    /**
     * The phone-remote page, opened by scanning the QR in the editor toolbar.
     * The deck's remote token is the whole credential; the phone can pair
     * before going live and just waits until the presenter screen opens.
     */
    public function __invoke(Presentation $presentation): Response
    {
        return Inertia::render('presentations/Remote', [
            'presentationName' => $presentation->name,
            'embedToken' => $presentation->embed_token,
            'remoteToken' => $presentation->remote_token,
            'sourceType' => $presentation->source['type'] ?? 'editor',
            'content' => $presentation->content,
            'flow' => $presentation->flow,
        ]);
    }
}
