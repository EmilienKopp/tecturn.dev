<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contacts\IndexContactsRequest;
use App\Infrastructure\ReadModels\ContactsReadModel;
use App\Support\Features;
use Inertia\Inertia;
use Inertia\Response;

class ContactsController extends Controller
{
    public function __construct(
        private readonly ContactsReadModel $contacts,
    ) {}

    public function __invoke(IndexContactsRequest $request): Response
    {
        $userId = $request->user()->id;
        $canDiscover = Features::discovery();
        $search = $canDiscover ? $request->validated('search') : null;

        return Inertia::render('Contacts', [
            'search' => $search ?? '',
            // Discovery is gated: without the flag the directory never surfaces
            // other people, whether browsing or searching.
            'results' => $canDiscover ? $this->contacts->directoryForUser($userId, $search) : [],
            'following' => $this->contacts->followingForUser($userId),
            'followers' => $this->contacts->followersForUser($userId),
            'followRequests' => $this->contacts->followRequestsForUser($userId),
            'followedTalks' => $this->contacts->talksFromPeopleUserFollows($userId),
        ]);
    }
}
