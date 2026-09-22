<?php

use App\Enums\RegistrationMode;

return [

    /*
    |--------------------------------------------------------------------------
    | Registration Mode
    |--------------------------------------------------------------------------
    |
    | Controls how new people get into the app. This is the default value for
    | the "registration" Pennant feature; once the flag is toggled at runtime
    | (e.g. from the admin panel) the stored value wins until it is purged.
    |
    | Supported: "open", "invitation", "closed"
    |
    */

    'registration' => env('REGISTRATION_MODE', RegistrationMode::Invitation->value),

    /*
    |--------------------------------------------------------------------------
    | People Discovery
    |--------------------------------------------------------------------------
    |
    | Controls whether the contacts directory can surface and search other
    | people. Off until per-user discoverable/public/private settings exist,
    | so nobody is exposed without opting in. Admins can toggle it at runtime.
    |
    */

    'discovery' => env('FEATURE_DISCOVERY', false),

];
