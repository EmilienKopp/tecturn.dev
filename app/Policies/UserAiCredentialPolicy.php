<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAiCredential;

class UserAiCredentialPolicy
{
    /**
     * A user may only manage their own AI credentials.
     */
    public function update(User $user, UserAiCredential $credential): bool
    {
        return $credential->user_id === $user->id;
    }

    public function delete(User $user, UserAiCredential $credential): bool
    {
        return $credential->user_id === $user->id;
    }
}
