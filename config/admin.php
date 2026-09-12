<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Authorized Admin Emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email addresses allowed to reach the admin
    | panel. This is an extra gate on top of WorkOS authentication: a user
    | must be authenticated AND their email must appear here. Matching is
    | case-insensitive.
    |
    */

    'emails' => collect(explode(',', (string) env('ADMIN_EMAILS', '')))
        ->map(fn (string $email): string => Str::lower(trim($email)))
        ->filter()
        ->values()
        ->all(),

    /*
    |--------------------------------------------------------------------------
    | Admin Domain
    |--------------------------------------------------------------------------
    |
    | When set (e.g. "admin.tecturn.dev"), the admin routes are served on this
    | dedicated subdomain via Route::domain(). When null, they fall back to a
    | "/admin" path prefix, which is convenient for local development.
    |
    | Sharing the WorkOS session across the subdomain and the main app requires
    | SESSION_DOMAIN to be set to the parent domain (e.g. ".tecturn.dev").
    |
    */

    'domain' => env('ADMIN_DOMAIN'),

];
