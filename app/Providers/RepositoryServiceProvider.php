<?php

namespace App\Providers;

use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Feedback\Contracts\FeedbackRepository;
use App\Domain\Networking\Contracts\UserFollowRepository;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Contracts\PresentationSessionRepository;
use App\Domain\Presentation\Contracts\RehearsalRepository;
use App\Domain\Presentation\Contracts\RehearsalReviewRepository;
use App\Domain\Presentation\Contracts\TranslationServiceContract;
use App\Infrastructure\Adapters\UnconfiguredTranslationService;
use App\Infrastructure\Adapters\YoYoTranslateAdapter;
use App\Infrastructure\Persistence\Repositories\EloquentBetaRequestRepository;
use App\Infrastructure\Persistence\Repositories\EloquentFeedbackRepository;
use App\Infrastructure\Persistence\Repositories\EloquentPresentationRepository;
use App\Infrastructure\Persistence\Repositories\EloquentPresentationSessionRepository;
use App\Infrastructure\Persistence\Repositories\EloquentRehearsalRepository;
use App\Infrastructure\Persistence\Repositories\EloquentRehearsalReviewRepository;
use App\Infrastructure\Persistence\Repositories\EloquentUserFollowRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PresentationRepository::class, EloquentPresentationRepository::class);
        $this->app->bind(PresentationSessionRepository::class, EloquentPresentationSessionRepository::class);
        $this->app->bind(RehearsalRepository::class, EloquentRehearsalRepository::class);
        $this->app->bind(RehearsalReviewRepository::class, EloquentRehearsalReviewRepository::class);
        $this->app->bind(BetaRequestRepository::class, EloquentBetaRequestRepository::class);
        $this->app->bind(FeedbackRepository::class, EloquentFeedbackRepository::class);
        $this->app->bind(UserFollowRepository::class, EloquentUserFollowRepository::class);

        $this->app->bind(TranslationServiceContract::class, function () {
            $apiKey = (string) config('yoyotranslate.api_key');

            if ($apiKey === '') {
                return new UnconfiguredTranslationService;
            }

            $http = Http::baseUrl((string) config('yoyotranslate.api_base_url'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson();

            return new YoYoTranslateAdapter($http);
        });
    }
}
