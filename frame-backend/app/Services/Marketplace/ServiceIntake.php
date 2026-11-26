<?php

namespace App\Services\Marketplace;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\EditorService;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Payments\Agreement;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;

final class ServiceIntake
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function visible(User $actor): Builder
    {
        $field = $actor->role === UserRole::Creator ? 'creator_id' : 'editor_id';

        return WorkItem::query()->where('type', WorkType::ServiceRequest->value)->where($field, $actor->id);
    }

    public function submit(User $actor, string $serviceId, array $input): WorkItem
    {
        $this->access->actor($actor, UserRole::Creator);
        $offer = EditorService::query()->where('status', ServiceStatus::Active->value)->findOrFail($serviceId);

        return $this->access->lockAccounts($actor, [$offer->editor_id], function (User $creator, array $users) use ($offer, $input) {
            $this->access->actor($creator, UserRole::Creator);
            $service = EditorService::query()->lockForUpdate()->findOrFail($offer->id);
            $this->checkOffer($service, $users[$service->editor_id], $creator);
            abort_if($this->visible($creator)->where('editor_service_id', $service->id)
                ->whereIn('status', [WorkStatus::Open->value, WorkStatus::PendingAgreement->value])->exists(), 409);

            abort_unless(hash_equals(Agreement::offer($service), $input['offer_fingerprint'] ?? ''), 409);
            $work = $creator->createdWorkItems()->make($input);
            $work->type = WorkType::ServiceRequest;
            $work->status = WorkStatus::Open;
            $work->editor_id = $service->editor_id;
            $work->editor_service_id = $service->id;
            $work->content_type_id = $service->content_type_id;
            $work->title = $service->title;
            $work->currency = $service->currency;
            $work->quantity = 1;
            $work->budget_min = $service->price;
            $work->budget_max = $service->price;
            $work->save();

            return $work;
        });
    }

    public function transition(User $actor, string $id, WorkStatus $target, string $fingerprint = ''): WorkItem
    {
        abort_unless(in_array($target, [WorkStatus::PendingAgreement, WorkStatus::Closed, WorkStatus::Cancelled], true), 409);
        $role = $target === WorkStatus::Cancelled ? UserRole::Creator : UserRole::Editor;
        $this->access->actor($actor, $role);
        $candidate = $this->visible($actor)->findOrFail($id);

        $accepting = $target === WorkStatus::PendingAgreement;
        $others = $accepting ? [$candidate->creator_id] : [];

        return $this->access->lockAccounts($actor, $others, function (User $user, array $users) use ($id, $role, $target, $accepting, $fingerprint) {
            $this->access->actor($user, $role);
            $work = $this->visible($user)->lockForUpdate()->findOrFail($id);
            abort_unless($work->status === WorkStatus::Open, 409);

            if ($accepting) {
                abort_unless($work->deadline === null || $work->deadline->isFuture(), 409);
                $creator = $this->access->actor($users[$work->creator_id], UserRole::Creator);
                $service = EditorService::query()->where('editor_id', $user->id)->lockForUpdate()->find($work->editor_service_id);
                abort_unless($service !== null, 409);
                $this->checkOffer($service, $user, $creator);
                abort_unless($service->content_type_id === $work->content_type_id && $service->currency === $work->currency
                    && $service->title === $work->title
                    && $service->price === $work->budget_min && $service->price === $work->budget_max, 409);
                abort_unless(hash_equals(Agreement::fingerprint(Agreement::terms($work)), $fingerprint), 409);
                $work->agreed_price = $work->budget_min;
            }
            $work->status = $target;
            $work->save();

            return $work;
        });
    }

    private function checkOffer(EditorService $service, User $editor, User $creator): void
    {
        abort_unless($service->status === ServiceStatus::Active && $service->content_type_id !== null
            && BigDecimal::of($service->price)->isPositive(), 409);
        $this->access->requireEditor($editor, $service->content_type_id, $creator);
    }
}
