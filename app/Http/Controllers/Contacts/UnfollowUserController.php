<?php

namespace App\Http\Controllers\Contacts;

use App\Application\Actions\Networking\UnfollowUser;
use App\Application\Commands\UnfollowUserCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\ToggleFollowRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UnfollowUserController extends Controller
{
    public function __construct(
        private readonly UnfollowUser $unfollowUser,
    ) {}

    public function __invoke(ToggleFollowRequest $request, User $user): RedirectResponse
    {
        $this->unfollowUser->execute(new UnfollowUserCommand(
            followerUserId: $request->user()->id,
            followedUserId: $user->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact unfollowed.')]);

        return back();
    }
}
