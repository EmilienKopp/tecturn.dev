<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\PresentationImagesView;
use Illuminate\Support\Facades\Storage;

class PresentationImageReadModel
{
    /**
     * Lists every content image uploaded across the team's presentations,
     * newest first, for reuse in the editor's image picker.
     *
     * The public URL is resolved through the file's own disk rather than baked
     * into the view, so it stays correct whether media lives on the local
     * `public` disk or S3. This mirrors Spatie's DefaultPathGenerator layout
     * ("{media_id}/{file_name}"), which is the generator this app is configured
     * with.
     *
     * @return array<int, array{url: string, name: string, presentation_id: int, presentation_name: string, created_at: string|null}>
     */
    public function listForTeam(int $teamId): array
    {
        return PresentationImagesView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PresentationImagesView $image): array => [
                'url' => Storage::disk($image->disk)->url($image->id.'/'.$image->file_name),
                'name' => $image->name,
                'presentation_id' => $image->presentation_id,
                'presentation_name' => $image->presentation_name,
                'created_at' => $image->created_at?->toISOString(),
            ])
            ->all();
    }
}
