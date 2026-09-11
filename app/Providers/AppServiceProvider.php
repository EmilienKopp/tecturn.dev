<?php

namespace App\Providers;

use App\Infrastructure\Broadcasting\AnonymousViewer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureViewerGuard();

        DevCommands::artisan('reverb:start', 'reverb');
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
