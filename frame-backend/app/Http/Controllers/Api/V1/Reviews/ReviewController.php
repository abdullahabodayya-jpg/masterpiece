<?php

namespace App\Http\Controllers\Api\V1\Reviews;

use App\Enums\UserRole;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Reviews\ReviewRequest;
use App\Http\Resources\Reviews\ReviewResource;
use App\Services\Reviews\ReviewWorkflow;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReviewController
{
    public function __construct(private readonly ReviewWorkflow $workflow) {}

    public function editor(CatalogRequest $request, string $editorId): JsonResponse
    {
        return $this->received($request, UserRole::Editor, $editorId);
    }

    public function creator(CatalogRequest $request, string $creatorId): JsonResponse
    {
        return $this->received($request, UserRole::Creator, $creatorId);
    }

    public function project(CatalogRequest $request, string $workItemId): JsonResponse
    {
        $actor = $request->user('sanctum');
        $work = $this->workflow->project($actor, $workItemId);
        $this->workflow->requireQualifyingWork($work);

        return $request->page($this->workflow->reviews($actor)->where('work_item_id', $work->id), ReviewResource::class);
    }

    public function status(Request $request, string $workItemId): JsonResponse
    {
        $actor = $request->user('sanctum');
        $work = $this->workflow->project($actor, $workItemId);

        return response()->json(['data' => $this->workflow->status($actor, $work)]);
    }

    public function store(ReviewRequest $request, string $workItemId): JsonResponse
    {
        $review = $this->workflow->publish($request->user('sanctum'), $workItemId, $request->validated());

        return ReviewResource::make($review)->response()->setStatusCode(201);
    }

    public function mine(CatalogRequest $request): JsonResponse
    {
        $id = $request->user('sanctum')->id;
        $reviews = $this->workflow->reviews()->where(fn (Builder $party) => $party
            ->where('reviewer_user_id', $id)->orWhere('reviewed_user_id', $id));

        return $request->page($reviews, ReviewResource::class);
    }

    private function received(CatalogRequest $request, UserRole $role, string $id): JsonResponse
    {
        $reviews = $this->workflow->publicReviews($role, $id, $request->user('sanctum'));
        $statistics = (clone $reviews)->selectRaw('COUNT(*) AS total, ROUND(AVG(rating), 2) AS average')->toBase()->first();
        $page = $request->page($reviews, ReviewResource::class);

        return response()->json($page->getData(true) + ['summary' => [
            'review_count' => (int) $statistics->total,
            'average_rating' => $statistics->average === null ? null : (string) BigDecimal::of($statistics->average)->toScale(2),
        ]]);
    }
}
