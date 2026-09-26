<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\AdminFeedbackView;

class AdminFeedbackReadModel
{
    /**
     * Every piece of feedback, newest first, shaped for the admin list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return AdminFeedbackView::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AdminFeedbackView $row): array => [
                'id' => $row->id,
                'message' => $row->message,
                'user_name' => $row->user_name,
                'user_email' => $row->user_email,
                'created_at' => $row->created_at?->toISOString(),
            ])
            ->all();
    }
}
