<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListReviewsController extends Controller
{
    public function __construct(private readonly RehearsalReviewReadModel $reviews) {}

    public function __invoke(Request $request): Response
    {
        $userId = $request->user()->id;

        return Inertia::render('reviews/Index', [
            'received' => $this->reviews->listForReviewer($userId),
            'requested' => $this->reviews->listForRequester($userId),
        ]);
    }
}
