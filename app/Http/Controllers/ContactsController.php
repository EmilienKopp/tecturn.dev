<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contacts\IndexContactsRequest;
use App\Infrastructure\ReadModels\ContactsReadModel;
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
        $search = $request->validated('search');

        return Inertia::render('Contacts', [
            'search' => $search ?? '',
            'results' => $this->contacts->directoryForUser($userId, $search),
            'following' => $this->contacts->followingForUser($userId),
            'followers' => $this->contacts->followersForUser($userId),
            'followedTalks' => $this->contacts->talksFromPeopleUserFollows($userId),
        ]);
    }
}
