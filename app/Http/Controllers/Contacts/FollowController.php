<?php

namespace App\Http\Controllers\Contacts;

use App\Application\Actions\Networking\FollowUser;
use App\Application\Actions\Networking\UnfollowUser;
use App\Application\Commands\FollowUserCommand;
use App\Application\Commands\UnfollowUserCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\ToggleFollowRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FollowController extends Controller
{
    public function __construct(
        private readonly FollowUser $followUser,
        private readonly UnfollowUser $unfollowUser,
    ) {}

    public function store(ToggleFollowRequest $request, User $user): RedirectResponse
    {
        $this->followUser->execute(new FollowUserCommand(
            followerUserId: $request->user()->id,
            followedUserId: $user->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow request sent.')]);

        return back();
    }

    public function destroy(ToggleFollowRequest $request, User $user): RedirectResponse
    {
        $this->unfollowUser->execute(new UnfollowUserCommand(
            followerUserId: $request->user()->id,
            followedUserId: $user->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact unfollowed.')]);

        return back();
    }
}
