<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\PracticeRunModel;
use App\Models\RehearsalReviewModel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class StreamRehearsalAudioController extends Controller
{
    /**
     * Serves the rehearsal voice recording to the run's team members and to
     * users asked to review the run. The raw media URL is never exposed —
     * this route is the only way to reach the file.
     */
    public function __invoke(Request $request, PracticeRunModel $practice_run): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->canListen($user, $practice_run), 403);

        $media = $practice_run->getFirstMedia(PracticeRunModel::RECORDING_COLLECTION);

        abort_unless($media instanceof Media, 404);

        return $media->toResponse($request);
    }

    private function canListen(User $user, PracticeRunModel $run): bool
    {
        if ($user->belongsToTeam($run->presentation->team)) {
            return true;
        }

        return RehearsalReviewModel::query()
            ->where('practice_run_id', $run->id)
            ->where('reviewer_user_id', $user->id)
            ->exists();
    }
}
