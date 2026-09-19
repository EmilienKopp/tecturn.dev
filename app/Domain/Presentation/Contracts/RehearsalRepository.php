<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\RehearsalEntity;

interface RehearsalRepository
{
    public function save(RehearsalEntity $run): RehearsalEntity;

    public function findById(int $id): RehearsalEntity;

    /** Attaches the rehearsal voice recording, replacing any existing one. */
    public function storeRecording(int $runId, string $filePath, string $fileName): void;
}
