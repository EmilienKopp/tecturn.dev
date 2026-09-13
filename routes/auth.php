<?php

use App\Application\Actions\Auth\ProvisionUserFromWorkOS;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;

Route::middleware(['guest'])->group(function () {
    Route::get('login', fn (AuthKitLoginRequest $request) => $request->redirect())->name('login');

    Route::get('authenticate', function (AuthKitAuthenticationRequest $request) {
        // Gate new-account creation behind the registration policy so a WorkOS
        // login (any method) can't bypass invitation-only access. find() also
        // re-links an account by verified email if its WorkOS id changed, so a
        // switched WorkOS app doesn't collide on the unique email.
        $provision = app(ProvisionUserFromWorkOS::class);

        $request->authenticate(
            findUsing: $provision->find(...),
            createUsing: $provision->create(...),
        );

        $user = auth()->user();
        $currentTeam = $user->currentTeam ?? $user->personalTeam();

        if ($currentTeam && ! $user->current_team_id) {
            $user->switchTeam($currentTeam);
        }

        if ($currentTeam) {
            URL::defaults(['current_team' => $currentTeam->slug]);
        }

        return redirect()->intended(route('dashboard'));
    });
});

Route::post('logout', fn (AuthKitLogoutRequest $request) => $request->logout())
    ->middleware(['auth'])->name('logout');
