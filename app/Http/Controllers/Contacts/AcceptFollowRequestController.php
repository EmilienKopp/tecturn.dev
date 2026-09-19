<?php

namespace App\Http\Controllers\Contacts;

use App\Application\Actions\Networking\AcceptFollowRequest;
use App\Application\Commands\RespondToFollowRequestCommand;
use App\Domain\Networking\Exceptions\FollowRequestNotFound;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcceptFollowRequestController extends Controller
{
    public function __construct(
        private readonly AcceptFollowRequest $acceptFollowRequest,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        try {
            $this->acceptFollowRequest->execute(new RespondToFollowRequestCommand(
                followerUserId: $user->id,
                followedUserId: $request->user()->id,
            ));

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow request accepted.')]);
        } catch (FollowRequestNotFound) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This follow request no longer exists.')]);
        }

        return back();
    }
}
