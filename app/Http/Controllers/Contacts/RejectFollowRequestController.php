<?php

namespace App\Http\Controllers\Contacts;

use App\Application\Actions\Networking\RejectFollowRequest;
use App\Application\Commands\RespondToFollowRequestCommand;
use App\Domain\Networking\Exceptions\FollowRequestNotFound;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RejectFollowRequestController extends Controller
{
    public function __construct(
        private readonly RejectFollowRequest $rejectFollowRequest,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        try {
            $this->rejectFollowRequest->execute(new RespondToFollowRequestCommand(
                followerUserId: $user->id,
                followedUserId: $request->user()->id,
            ));

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow request declined.')]);
        } catch (FollowRequestNotFound) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This follow request no longer exists.')]);
        }

        return back();
    }
}
