<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Domain\Presentation\ValueObjects\TalkSettings;
use App\Events\Presentations\ReactionSent;
use App\Http\Controllers\Controller;
use App\Models\Presentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SendReactionController extends Controller
{
    public function __invoke(Request $request, Presentation $presentation): Response
    {
        $allowed = TalkSettings::fromArray($presentation->talk_settings ?? [])->reactions;

        $validated = $request->validate([
            'emoji' => ['required', 'string', Rule::in($allowed)],
        ]);

        ReactionSent::dispatch($presentation->embed_token, $validated['emoji']);

        return response()->noContent();
    }
}
