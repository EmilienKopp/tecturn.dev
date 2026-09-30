<?php

use App\Models\Presentation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Live "watching now" presence for a talk. Anyone holding the (public) embed
 * token may join. Everyone (audience and presenter alike) authorizes through
 * the anonymous "viewer" guard so the presence member is always the
 * client-supplied viewer id, never a real user identity. Presenters prefix
 * theirs with "presenter:" so the dock can count the audience without counting
 * itself.
 */
/**
 * Presenter ↔ phone-remote control lane. Private so client events (whispers)
 * carry nav commands and buzzer hits without a server round trip. Knowing the
 * deck's remote token is the credential: the presenter screen gets it from
 * the backend, the phone from the QR in the editor. Audience members hold
 * only the embed token, so they can't join and can't drive the deck.
 */
Broadcast::channel('presentation-control.{embedToken}', function (Authenticatable $user, string $embedToken): bool {
    $token = (string) request('remote_token');

    if ($token === '') {
        return false;
    }

    return Presentation::query()
        ->where('embed_token', $embedToken)
        ->where('remote_token', $token)
        ->exists();
}, ['guards' => ['viewer']]);

Broadcast::channel('presentation-live.{embedToken}', function (Authenticatable $user, string $embedToken): array|false {
    if (! Presentation::where('embed_token', $embedToken)->exists()) {
        return false;
    }

    $id = (string) $user->getAuthIdentifier();

    return [
        'role' => str_starts_with($id, 'presenter:') ? 'presenter' : 'viewer',
    ];
}, ['guards' => ['viewer']]);
