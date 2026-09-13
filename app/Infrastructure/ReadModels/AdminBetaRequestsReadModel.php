<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\User;
use App\Models\Views\AdminBetaRequestView;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminBetaRequestsReadModel
{
    /**
     * How long a request lingers after its requester has an account before it
     * drops off the review list.
     */
    private const STALE_AFTER_WEEKS = 2;

    /**
     * Every beta request worth reviewing, newest first, shaped for the admin
     * list. The email column is stored encrypted, so it is decrypted here for
     * display and to match against existing accounts. Requests whose requester
     * already has an account are flagged as "registered"; once that account is
     * older than two weeks the request is dropped entirely.
     *
     * The account correlation lives in PHP rather than the SQL view because the
     * stored email is non-deterministic ciphertext, so it can't be joined on.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     message: string|null,
     *     status: string,
     *     registered: bool,
     *     created_at: string|null
     * }>
     */
    public function all(): array
    {
        $requests = AdminBetaRequestView::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AdminBetaRequestView $request): array => [
                'id' => $request->id,
                'name' => $request->name,
                'email' => $this->decryptEmail($request->email),
                'message' => $request->message,
                'status' => $request->status->value,
                'created_at' => $request->created_at?->toISOString(),
            ]);

        $accounts = $this->accountsFor($requests->pluck('email'));
        $staleAfter = now()->subWeeks(self::STALE_AFTER_WEEKS);

        return $requests
            ->map(function (array $request) use ($accounts): array {
                $request['registered_at'] = $accounts->get(Str::lower($request['email']));
                $request['registered'] = $request['registered_at'] !== null;

                return $request;
            })
            ->reject(fn (array $request): bool => $request['registered_at']?->lt($staleAfter) ?? false)
            ->map(fn (array $request): array => Arr::except($request, 'registered_at'))
            ->values()
            ->all();
    }

    /**
     * Account creation times keyed by lower-cased email, for the requesters who
     * already have an account.
     *
     * @param  Collection<int, string>  $emails
     * @return Collection<string, CarbonInterface|null>
     */
    private function accountsFor(Collection $emails): Collection
    {
        $needles = $emails
            ->filter()
            ->map(fn (string $email): string => Str::lower($email))
            ->unique()
            ->values()
            ->all();

        return User::query()
            ->whereIn(DB::raw('lower(email)'), $needles)
            ->get(['email', 'created_at'])
            ->keyBy(fn (User $user): string => Str::lower($user->email))
            ->map(fn (User $user): ?CarbonInterface => $user->created_at);
    }

    /**
     * Mirror of the Eloquent "encrypted" cast (Crypt::decrypt with no
     * serialization). Falls back gracefully if a row can't be decrypted.
     */
    private function decryptEmail(string $ciphertext): string
    {
        try {
            return Crypt::decrypt($ciphertext, false);
        } catch (DecryptException) {
            return '';
        }
    }
}
