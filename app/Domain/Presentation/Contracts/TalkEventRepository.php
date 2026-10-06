<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\TalkEventEntity;

interface TalkEventRepository
{
    public function save(TalkEventEntity $event): TalkEventEntity;

    public function findById(int $id): TalkEventEntity;

    public function deleteById(int $id): void;
}
