<?php

namespace App\Http\Resources\Payments;

use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payments\PaymentAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fields = match (true) {
            $this->resource instanceof Payment => ['id', 'work_item_id', 'amount', 'currency', 'platform_fee', 'editor_net_amount', 'paid_at'],
            $this->resource instanceof Refund => ['id', 'payment_id', 'amount', 'currency', 'completed_at'],
            default => ['id', 'work_item_id', 'payment_id', 'editor_id', 'amount', 'currency', 'paid_at'],
        };

        return $this->only($fields) + [
            'mode' => 'academic_simulation', 'status' => $this->status->value,
            'decision_expired' => $this->when($this->resource instanceof Refund, fn () => $this->status === RefundStatus::Pending
                && $this->provider_refund_id === null && $this->created_at->lte(PaymentAccess::refundCutoff())),
            'refunds' => self::collection($this->whenLoaded('refunds')),
        ];
    }
}
