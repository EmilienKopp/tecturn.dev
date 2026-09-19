<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\RehearsalRepository;
use App\Domain\Presentation\Entities\RehearsalEntity;
use App\Models\RehearsalModel;

class EloquentRehearsalRepository implements RehearsalRepository
{
    public function save(RehearsalEntity $run): RehearsalEntity
    {
        $attributes = [
            'presentation_id' => $run->presentation_id,
            'team_id' => $run->team_id,
            'started_at' => $run->started_at,
            'ended_at' => $run->ended_at,
            'duration_seconds' => $run->duration_seconds,
            'slide_timings' => $run->slide_timings,
            'step_events' => $run->step_events,
            'content' => $run->content,
            'flow' => $run->flow,
        ];

        if ($run->id === null) {
            $model = RehearsalModel::create($attributes);
        } else {
            $model = RehearsalModel::findOrFail($run->id);
            $model->update($attributes);
        }

        return $model->refresh()->toEntity();
    }

    public function findById(int $id): RehearsalEntity
    {
        return RehearsalModel::findOrFail($id)->toEntity();
    }

    public function storeRecording(int $runId, string $filePath, string $fileName): void
    {
        RehearsalModel::findOrFail($runId)
            ->addMedia($filePath)
            ->usingFileName($fileName)
            ->toMediaCollection(RehearsalModel::RECORDING_COLLECTION);
    }
}
