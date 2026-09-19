<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BrandingUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /**
     * Show the user's branding and editor defaults settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Branding');
    }

    /**
     * Update the user's branding palette.
     */
    public function update(BrandingUpdateRequest $request): RedirectResponse
    {
        $request->user()->update(['branding' => $request->palette()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('branding.edit');
    }
}
