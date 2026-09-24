<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Enums\BetaRequestStatus;
use App\Models\BetaRequest;
use Illuminate\Support\Str;

class EloquentBetaRequestRepository implements BetaRequestRepository
{
    public function save(BetaRequestEntity $request): BetaRequestEntity
    {
        $model = BetaRequest::updateOrCreate(
            ['email_hash' => $this->hashEmail($request->email)],
            [
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
                'status' => $request->status,
            ],
        );

        return $model->refresh()->toEntity();
    }

    public function findById(int $id): BetaRequestEntity
    {
        return BetaRequest::findOrFail($id)->toEntity();
    }

    public function hasApprovedRequestForEmail(string $email): bool
    {
        return BetaRequest::query()
            ->where('email_hash', $this->hashEmail($email))
            ->where('status', BetaRequestStatus::Approved)
            ->exists();
    }

    public function hasActiveRequestForEmail(string $email): bool
    {
        return BetaRequest::query()
            ->where('email_hash', $this->hashEmail($email))
            ->whereIn('status', [BetaRequestStatus::Pending, BetaRequestStatus::Approved])
            ->exists();
    }

    /**
     * Deterministic hash of the address, used as the unique dedupe key since the
     * email column itself is encrypted (non-deterministic) ciphertext.
     */
    private function hashEmail(string $email): string
    {
        return hash('sha256', Str::lower(trim($email)));
    }
}
