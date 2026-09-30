<?php

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\PresentationImageReadModel;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class ListPresentationImagesController extends Controller
{
    public function __construct(
        private readonly PresentationImageReadModel $images,
    ) {}

    public function __invoke(Team $current_team): JsonResponse
    {
        return response()->json([
            'images' => $this->images->listForTeam($current_team->id),
        ]);
    }
}
