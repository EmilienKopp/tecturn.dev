<?php

namespace App\Http\Controllers\Contacts;

use App\Application\Actions\Networking\FollowUser;
use App\Application\Commands\FollowUserCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\ToggleFollowRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FollowUserController extends Controller
{
    public function __construct(
        private readonly FollowUser $followUser,
    ) {}

    public function __invoke(ToggleFollowRequest $request, User $user): RedirectResponse
    {
        $this->followUser->execute(new FollowUserCommand(
            followerUserId: $request->user()->id,
            followedUserId: $user->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow request sent.')]);

        return back();
    }
}
