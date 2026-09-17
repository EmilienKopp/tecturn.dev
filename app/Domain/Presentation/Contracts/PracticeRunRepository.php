<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\PracticeRunEntity;

interface PracticeRunRepository
{
    public function save(PracticeRunEntity $run): PracticeRunEntity;

    public function findById(int $id): PracticeRunEntity;

    /** Attaches the rehearsal voice recording, replacing any existing one. */
    public function storeRecording(int $runId, string $filePath, string $fileName): void;
}
