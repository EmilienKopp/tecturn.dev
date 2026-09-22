<?php

namespace App\Providers;

use App\Infrastructure\Broadcasting\AnonymousViewer;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use Splitstack\Teddy\TeddyServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDevelopmentProviders();
    }

    /**
     * Register dev-only package providers behind an existence guard so a
     * require-dev package (e.g. Teddy) never breaks a production boot where
     * the class is absent under `composer install --no-dev`.
     */
    protected function registerDevelopmentProviders(): void
    {
        if (class_exists(TeddyServiceProvider::class)) {
            $this->app->register(TeddyServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureViewerGuard();
        $this->configureFeatures();

        DevCommands::artisan('reverb:start', 'reverb');
    }

    /**
     * Register the application's global feature flags. The registration flag is
     * a rich (string) value seeded from config; runtime overrides win once set.
     */
    protected function configureFeatures(): void
    {
        Feature::define('registration', fn (): string => config('features.registration'));

        // People discovery is a global toggle seeded from config; it stays off
        // until stricter per-user discoverability settings ship.
        Feature::define('discovery', fn (): bool => (bool) config('features.discovery'));

        // Team-scoped flags default on, so existing teams keep their capabilities
        // until an admin explicitly turns one off for a given team.
        Feature::define('live_translation', fn (Team $team): bool => true);
    }

    /**
     * Resolve anonymous audience members from a client-supplied viewer id so
     * they can authorize on the guarded live presence channel without logging
     * in. The id is opaque (a random UUID) and only names a presence member.
     */
    protected function configureViewerGuard(): void
    {
        Auth::viaRequest('viewer', function (Request $request): ?AnonymousViewer {
            $viewerId = $request->input('viewer_id');

            return is_string($viewerId) && $viewerId !== ''
                ? new AnonymousViewer($viewerId)
                : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
