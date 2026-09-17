<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\PracticeRunHistoryView;
use App\Models\Views\RehearsalReviewDetailView;
use App\Models\Views\ReviewCommentDetailView;

class RehearsalReviewReadModel
{
    /**
     * Reviews someone has asked this user to do, pending first.
     *
     * @return array<int, array{
     *     id: int,
     *     practice_run_id: int,
     *     presentation_name: string,
     *     requester_name: string,
     *     requester_avatar: string|null,
     *     status: string,
     *     rehearsed_at: string,
     *     duration_seconds: int,
     *     requested_at: string|null
     * }>
     */
    public function listForReviewer(int $userId): array
    {
        return RehearsalReviewDetailView::query()
            ->where('reviewer_user_id', $userId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RehearsalReviewDetailView $review): array => [
                'id' => $review->id,
                'practice_run_id' => $review->practice_run_id,
                'presentation_name' => $review->presentation_name,
                'requester_name' => $review->requester_name,
                'requester_avatar' => $review->requester_avatar,
                'status' => $review->status,
                'rehearsed_at' => $review->rehearsed_at->toISOString(),
                'duration_seconds' => $review->duration_seconds,
                'requested_at' => $review->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * Everything the reviewer's page needs: the review, the frozen run
     * snapshot, and the comments left so far.
     *
     * @return array{
     *     id: int,
     *     practice_run_id: int,
     *     status: string,
     *     presentation_name: string,
     *     requester_name: string,
     *     requester_avatar: string|null,
     *     rehearsed_at: string,
     *     duration_seconds: int,
     *     content: array<string, mixed>,
     *     flow: array<string, mixed>|null,
     *     step_events: list<array{at_ms: int, slide: int, step: int}>,
     *     has_recording: bool,
     *     comments: array<int, array{id: int, slide_number: int, message: string, created_at: string|null}>
     * }
     */
    public function findForReviewPage(int $reviewId): array
    {
        $review = RehearsalReviewDetailView::query()->findOrFail($reviewId);
        $run = PracticeRunHistoryView::query()->findOrFail($review->practice_run_id);

        return [
            'id' => $review->id,
            'practice_run_id' => $review->practice_run_id,
            'status' => $review->status,
            'presentation_name' => $review->presentation_name,
            'requester_name' => $review->requester_name,
            'requester_avatar' => $review->requester_avatar,
            'rehearsed_at' => $review->rehearsed_at->toISOString(),
            'duration_seconds' => $review->duration_seconds,
            'content' => $run->content,
            'flow' => $run->flow,
            'step_events' => $run->stepEvents(),
            'has_recording' => $run->has_recording,
            'comments' => ReviewCommentDetailView::query()
                ->where('rehearsal_review_id', $review->id)
                ->orderBy('slide_number')
                ->orderBy('created_at')
                ->get()
                ->map(fn (ReviewCommentDetailView $comment): array => [
                    'id' => $comment->id,
                    'slide_number' => $comment->slide_number,
                    'message' => $comment->message,
                    'created_at' => $comment->created_at?->toISOString(),
                ])
                ->all(),
        ];
    }

    /**
     * The requester's side: all reviews on one rehearsal run, each with its
     * reviewer and comments.
     *
     * @return array<int, array{
     *     id: int,
     *     reviewer_user_id: int,
     *     reviewer_name: string,
     *     reviewer_avatar: string|null,
     *     status: string,
     *     requested_at: string|null,
     *     comments: array<int, array{id: int, slide_number: int, message: string, created_at: string|null}>
     * }>
     */
    public function listForRun(int $runId): array
    {
        $comments = ReviewCommentDetailView::query()
            ->where('practice_run_id', $runId)
            ->orderBy('slide_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy('rehearsal_review_id');

        return RehearsalReviewDetailView::query()
            ->where('practice_run_id', $runId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RehearsalReviewDetailView $review): array => [
                'id' => $review->id,
                'reviewer_user_id' => $review->reviewer_user_id,
                'reviewer_name' => $review->reviewer_name,
                'reviewer_avatar' => $review->reviewer_avatar,
                'status' => $review->status,
                'requested_at' => $review->created_at?->toISOString(),
                'comments' => $comments->get($review->id, collect())
                    ->map(fn (ReviewCommentDetailView $comment): array => [
                        'id' => $comment->id,
                        'slide_number' => $comment->slide_number,
                        'message' => $comment->message,
                        'created_at' => $comment->created_at?->toISOString(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }
}
