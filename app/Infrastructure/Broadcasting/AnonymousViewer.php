<?php

declare(strict_types=1);

namespace App\Infrastructure\Broadcasting;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A login-less identity for audience members (and the presenter) on the live
 * presence channel. Presence channels are guarded, so anonymous viewers need
 * something Authenticatable to pass Reverb's auth check. The id it carries is
 * the client-supplied viewer id, which becomes the presence member id.
 */
class AnonymousViewer implements Authenticatable
{
    public function __construct(public readonly string $id) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
