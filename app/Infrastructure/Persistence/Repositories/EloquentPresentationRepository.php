<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Models\Presentation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EloquentPresentationRepository implements PresentationRepository
{
    public function findById(int $id): PresentationEntity
    {
        return Presentation::findOrFail($id)->toEntity();
    }

    public function save(PresentationEntity $presentation): PresentationEntity
    {
        $attributes = [
            'team_id' => $presentation->team_id,
            'name' => $presentation->name,
            'is_private' => $presentation->isPrivate,
            'content' => $presentation->content->toArray(),
            'talk_settings' => $presentation->talkSettings->toArray(),
            'flow' => $presentation->flow?->toArray(),
            'source' => $presentation->source->toArray(),
            'yoyotranslate_session_id' => $presentation->yoyotranslateSessionId,
            'yoyotranslate_session_started_at' => $presentation->yoyotranslateSessionStartedAt,
            'yoyotranslate_languages' => $presentation->yoyotranslateLanguages,
            'draft_plan' => $presentation->draftPlan,
            'draft_requested_at' => $presentation->draftRequestedAt,
            'draft_completed_at' => $presentation->draftCompletedAt,
            'draft_failed_at' => $presentation->draftFailedAt,
            'draft_error' => $presentation->draftError,
        ];

        if ($presentation->id === null) {
            $model = Presentation::create($attributes);
        } else {
            $model = Presentation::findOrFail($presentation->id);
            $model->update($attributes);
        }

        return $model->refresh()->toEntity();
    }

    public function delete(int $id): void
    {
        Presentation::whereKey($id)->delete();
    }

    public function storeBackgroundImage(int $id, string $filePath, string $fileName): string
    {
        $model = Presentation::findOrFail($id);

        $media = $model->addMedia($filePath)
            ->usingFileName($fileName)
            ->toMediaCollection(Presentation::BACKGROUND_COLLECTION);

        return $media->getFullUrl();
    }

    public function clearBackgroundImage(int $id): void
    {
        Presentation::findOrFail($id)
            ->clearMediaCollection(Presentation::BACKGROUND_COLLECTION);
    }

    public function storeSourcePdf(int $id, string $filePath, string $fileName): string
    {
        $model = Presentation::findOrFail($id);

        $media = $model->addMedia($filePath)
            ->usingFileName($fileName)
            ->toMediaCollection(Presentation::SOURCE_COLLECTION);

        return $media->getFullUrl();
    }

    public function storeImage(int $id, string $filePath, string $fileName): string
    {
        $model = Presentation::findOrFail($id);

        $media = $model->addMedia($filePath)
            ->usingFileName($fileName)
            ->toMediaCollection(Presentation::IMAGES_COLLECTION);

        return $media->getFullUrl();
    }

    public function storeImageFromUrl(int $id, string $url): string
    {
        $model = Presentation::findOrFail($id);

        $response = Http::connectTimeout(10)->timeout(20)->get($url);

        if ($response->failed()) {
            throw new RuntimeException("Failed to download image from {$url} (status {$response->status()}).");
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'lecturn-import-');
        file_put_contents($temporaryPath, $response->body());

        try {
            // The collection's accepted mime types reject anything that isn't an image.
            $media = $model->addMedia($temporaryPath)
                ->usingFileName($this->fileNameForUrl($url))
                ->toMediaCollection(Presentation::IMAGES_COLLECTION);
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return $media->getFullUrl();
    }

    public function clearImages(int $id): void
    {
        Presentation::findOrFail($id)
            ->clearMediaCollection(Presentation::IMAGES_COLLECTION);
    }

    private function fileNameForUrl(string $url): string
    {
        $name = urldecode(basename((string) parse_url($url, PHP_URL_PATH)));

        return $name !== '' ? $name : 'image';
    }
}
