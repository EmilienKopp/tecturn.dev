<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\AdminOverviewReadModel;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminOverviewReadModel $overview,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'overview' => $this->overview->summary(),
        ]);
    }
}
