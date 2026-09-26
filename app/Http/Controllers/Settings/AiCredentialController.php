<?php

namespace App\Http\Controllers\Settings;

use App\Ai\AiCredentialResolver;
use App\Ai\DecksterModels;
use App\Ai\ResolvedAiCredential;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiCredentialStoreRequest;
use App\Models\UserAiCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

use function Laravel\Ai\agent;

/**
 * "Bring your own AI" credentials for deck generation. Thin controller: writes
 * go straight to the model (as with branding settings). The API key is only
 * ever written, never read back to the client.
 */
class AiCredentialController extends Controller
{
    public function index(Request $request): Response
    {
        $credentials = $request->user()->aiCredentials()
            ->latest()
            ->get()
            ->map(fn (UserAiCredential $credential): array => [
                'id' => $credential->id,
                'label' => $credential->label,
                'driver' => $credential->driver,
                'model' => $credential->model,
                'base_url' => $credential->base_url,
                'masked_key' => $credential->maskedKey(),
                'is_default' => $credential->is_default,
            ]);

        return Inertia::render('settings/AiCredentials', [
            'credentials' => $credentials,
            'curatedModels' => DecksterModels::curated(),
            'freetextDrivers' => DecksterModels::freetextDrivers(),
            'requiresBaseUrl' => config('deckster.requires_base_url', []),
            'house' => DecksterModels::house(),
        ]);
    }

    public function store(AiCredentialStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $attributes = $request->credential();

        // The first credential is always the default; otherwise honor the flag.
        $isFirst = ! $user->aiCredentials()->exists();
        $makeDefault = $isFirst || $attributes['is_default'];
        $attributes['is_default'] = $makeDefault;

        DB::transaction(function () use ($user, $attributes, $makeDefault): void {
            if ($makeDefault) {
                $user->aiCredentials()->update(['is_default' => false]);
            }

            $user->aiCredentials()->create($attributes);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI credential saved.')]);

        return to_route('ai-credentials.index');
    }

    public function setDefault(Request $request, UserAiCredential $aiCredential): RedirectResponse
    {
        Gate::authorize('update', $aiCredential);

        DB::transaction(function () use ($request, $aiCredential): void {
            $request->user()->aiCredentials()->update(['is_default' => false]);
            $aiCredential->update(['is_default' => true]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Default AI model updated.')]);

        return to_route('ai-credentials.index');
    }

    public function destroy(UserAiCredential $aiCredential): RedirectResponse
    {
        Gate::authorize('delete', $aiCredential);

        $wasDefault = $aiCredential->is_default;
        $userId = $aiCredential->user_id;
        $aiCredential->delete();

        // Promote another credential so the user is never left with none marked
        // default while others exist.
        if ($wasDefault) {
            UserAiCredential::where('user_id', $userId)
                ->latest()
                ->first()
                ?->update(['is_default' => true]);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('AI credential removed.')]);

        return to_route('ai-credentials.index');
    }

    /**
     * Live connectivity check against the supplied credential before saving.
     * Runs a tiny prompt so a bad key or endpoint fails here, not mid-build.
     */
    public function test(AiCredentialStoreRequest $request, AiCredentialResolver $resolver): JsonResponse
    {
        $attributes = $request->credential();

        $resolved = new ResolvedAiCredential(
            driver: $attributes['driver'],
            model: $attributes['model'],
            apiKey: $attributes['api_key'],
            baseUrl: $attributes['base_url'],
        );

        ['provider' => $provider, 'model' => $model] = $resolver->apply($resolved);

        try {
            $reply = (string) agent(
                instructions: 'You are a connectivity check. Reply with the single word OK.',
            )->prompt('Say OK.', provider: $provider, model: $model, timeout: 20);

            return response()->json([
                'ok' => true,
                'message' => __('Connection succeeded.'),
                'sample' => mb_substr(trim($reply), 0, 60),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
