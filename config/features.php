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

];
