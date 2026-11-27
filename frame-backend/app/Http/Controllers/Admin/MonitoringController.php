<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\CommentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\PostStatus;
use App\Enums\PostVisibility;
use App\Enums\RefundStatus;
use App\Enums\ServiceStatus;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\Comment;
use App\Models\EditorService;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Post;
use App\Models\Refund;
use App\Models\Review;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MonitoringController
{
    public function index(Request $request, string $collection): JsonResponse
    {
        [$query, $statusEnum, $searchColumn, $scopeColumn] = $this->records($collection);
        $rules = [
            'page' => ['sometimes', 'integer', 'between:1,21474836'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'status' => $statusEnum ? ['sometimes', Rule::enum($statusEnum)] : ['missing'],
            'search' => $searchColumn ? ['sometimes', 'required', 'string', 'max:100'] : ['missing'],
            'reference_id' => $scopeColumn ? ['sometimes', 'uuid'] : ['missing'],
            'currency' => $this->financial($collection) ? ['sometimes', 'regex:/^[A-Z]{3}$/D'] : ['missing'],
        ];
        $filters = $request->validate($rules);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['search'])) {
            $query->where($searchColumn, 'like', '%'.addcslashes($filters['search'], '\\%_').'%');
        }
        if (isset($filters['reference_id'])) {
            $query->where($scopeColumn, $filters['reference_id']);
        }
        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        $page = $query->orderByDesc('created_at')->orderByDesc('id')->simplePaginate((int) ($filters['per_page'] ?? 20));

        return response()->json(['data' => $page->items(), 'meta' => [
            'page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'has_more' => $page->hasMorePages(),
        ]] + $this->mode($collection));
    }

    public function show(string $collection, string $id): JsonResponse
    {
        [$query] = $this->records($collection);

        return response()->json(['data' => $query->findOrFail($id)] + $this->mode($collection));
    }

    private function records(string $collection): array
    {
        $sandbox = fn (Builder $query) => $query->where('provider_name', 'stripe_sandbox');

        return match ($collection) {
            'jobs', 'service-requests', 'projects', 'work-items' => [
                $this->work($collection), WorkStatus::class, 'title', 'creator_id',
            ],
            'applications' => [JobApplication::query()->select([
                'id', 'work_item_id', 'editor_id', 'proposed_price', 'estimated_delivery_days', 'status', 'created_at',
            ]), ApplicationStatus::class, null, 'work_item_id'],
            'services' => [EditorService::withTrashed()->select([
                'id', 'editor_id', 'content_type_id', 'title', 'price', 'currency', 'delivery_days', 'status', 'created_at', 'deleted_at',
            ]), ServiceStatus::class, 'title', 'editor_id'],
            'payments' => [$sandbox(Payment::query())->select([
                'id', 'work_item_id', 'creator_id', 'amount', 'currency', 'platform_fee', 'editor_net_amount', 'status', 'created_at', 'paid_at',
            ]), PaymentStatus::class, null, 'work_item_id'],
            'refunds' => [Refund::query()->whereHas('payment', $sandbox)->select([
                'id', 'payment_id', 'amount', 'currency', 'status', 'created_at', 'completed_at',
            ]), RefundStatus::class, null, 'payment_id'],
            'payouts' => [Payout::query()->whereHas('payment', $sandbox)->select([
                'id', 'work_item_id', 'payment_id', 'editor_id', 'amount', 'currency', 'status', 'created_at', 'paid_at',
            ]), PayoutStatus::class, null, 'work_item_id'],
            'posts' => [Post::query()->select(['id', 'author_id', 'visibility', 'status', 'created_at'])
                ->selectRaw('CASE WHEN visibility = ? THEN body END AS body', [PostVisibility::Public->value]),
                PostStatus::class, null, 'author_id'],
            'comments' => [Comment::query()->select(['id', 'post_id', 'author_id', 'status', 'created_at'])
                ->selectRaw('CASE WHEN EXISTS (SELECT 1 FROM posts WHERE posts.id = comments.post_id AND posts.visibility = ?) THEN body END AS body',
                    [PostVisibility::Public->value]), CommentStatus::class, null, 'post_id'],
            'reviews' => [Review::withTrashed()->select([
                'id', 'work_item_id', 'reviewer_user_id', 'reviewed_user_id', 'rating', 'comment', 'created_at', 'deleted_at',
            ]), null, null, 'work_item_id'],
        };
    }

    private function work(string $collection): Builder
    {
        $query = WorkItem::query()->select([
            'id', 'creator_id', 'editor_id', 'editor_service_id', 'content_type_id', 'title', 'type', 'status',
            'currency', 'deadline', 'agreed_at', 'started_at', 'completed_at', 'created_at',
        ]);

        return match ($collection) {
            'jobs' => $query->where('type', WorkType::Job->value),
            'service-requests' => $query->where('type', WorkType::ServiceRequest->value),
            'projects' => $query->whereNotNull('started_at'),
            default => $query,
        };
    }

    private function financial(string $collection): bool
    {
        return in_array($collection, ['payments', 'refunds', 'payouts'], true);
    }

    private function mode(string $collection): array
    {
        return $this->financial($collection) ? ['mode' => 'academic_simulation'] : [];
    }
}
