<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\AdminBetaRequestView;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class AdminBetaRequestsReadModel
{
    /**
     * Every beta request, newest first, shaped for the admin review list. The
     * email column is stored encrypted, so it is decrypted here for display.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     message: string|null,
     *     status: string,
     *     created_at: string|null
     * }>
     */
    public function all(): array
    {
        return AdminBetaRequestView::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AdminBetaRequestView $request): array => [
                'id' => $request->id,
                'name' => $request->name,
                'email' => $this->decryptEmail($request->email),
                'message' => $request->message,
                'status' => $request->status->value,
                'created_at' => $request->created_at?->toISOString(),
            ])
            ->all();
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
