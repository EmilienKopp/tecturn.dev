<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\PracticeRunEntity;

interface PracticeRunRepository
{
    public function save(PracticeRunEntity $run): PracticeRunEntity;
}
