<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Domain\Presentation\ValueObjects\TalkSettings;
use App\Events\Presentations\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Presentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SendMessageController extends Controller
{
    public function __invoke(Request $request, Presentation $presentation): Response
    {
        $settings = TalkSettings::fromArray($presentation->talk_settings ?? []);

        abort_unless($settings->allowFreeText, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:'.$settings->freeTextMaxLength],
        ]);

        $message = trim($validated['message']);

        if ($message !== '') {
            MessageSent::dispatch($presentation->embed_token, $message, (string) Str::uuid());
        }

        return response()->noContent();
    }
}
