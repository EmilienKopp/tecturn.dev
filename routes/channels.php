<?php

use App\Models\PresentationModel;
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
Broadcast::channel('presentation-live.{embedToken}', function (Authenticatable $user, string $embedToken): array|false {
    if (! PresentationModel::where('embed_token', $embedToken)->exists()) {
        return false;
    }

    $id = (string) $user->getAuthIdentifier();

    return [
        'role' => str_starts_with($id, 'presenter:') ? 'presenter' : 'viewer',
    ];
}, ['guards' => ['viewer']]);
