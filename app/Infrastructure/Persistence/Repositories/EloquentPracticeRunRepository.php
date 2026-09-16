<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\PracticeRunRepository;
use App\Domain\Presentation\Entities\PracticeRunEntity;
use App\Models\PracticeRunModel;

class EloquentPracticeRunRepository implements PracticeRunRepository
{
    public function save(PracticeRunEntity $run): PracticeRunEntity
    {
        $attributes = [
            'presentation_id' => $run->presentation_id,
            'team_id' => $run->team_id,
            'started_at' => $run->started_at,
            'ended_at' => $run->ended_at,
            'duration_seconds' => $run->duration_seconds,
            'slide_timings' => $run->slide_timings,
            'content' => $run->content,
            'flow' => $run->flow,
        ];

        if ($run->id === null) {
            $model = PracticeRunModel::create($attributes);
        } else {
            $model = PracticeRunModel::findOrFail($run->id);
            $model->update($attributes);
        }

        return $model->refresh()->toEntity();
    }
}
