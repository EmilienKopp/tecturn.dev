<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\AdminUsersReadModel;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController extends Controller
{
    public function __construct(
        private readonly AdminUsersReadModel $users,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/Users', [
            'users' => $this->users->all(),
        ]);
    }
}
