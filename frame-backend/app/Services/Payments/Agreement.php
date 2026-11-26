<?php

namespace App\Services\Payments;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\EditorService;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class Agreement
{
    public function __construct(private readonly PaymentAccess $access) {}

    public static function terms(WorkItem $work): array
    {
        return [
            ...$work->only(['id', 'type', 'creator_id', 'editor_id', 'editor_service_id', 'title', 'description', 'content_type_id',
                'editing_tool_id', 'quantity', 'currency']),
            'price' => $work->agreed_price ?? $work->budget_min,
            'deadline' => $work->deadline?->toISOString(),
        ];
    }

    public static function selection(WorkItem $work, JobApplication $proposal): string
    {
        return self::fingerprint(array_replace(self::terms($work), [
            'editor_id' => $proposal->editor_id, 'price' => $proposal->proposed_price,
            'application_id' => $proposal->id, 'cover_message' => $proposal->cover_message,
            'estimated_delivery_days' => $proposal->estimated_delivery_days,
        ]));
    }

    public static function offer(EditorService $service): string
    {
        return self::fingerprint($service->only(['id', 'editor_id', 'title', 'description', 'content_type_id', 'price', 'currency', 'delivery_days']));
    }

    public static function fingerprint(array $terms): string
    {
        return hash('sha256', json_encode($terms, JSON_THROW_ON_ERROR));
    }

    public function forWork(WorkItem $work): array
    {
        $terms = self::terms($work);
        if ($work->type === WorkType::Job) {
            $selected = $work->jobApplications()->where('status', ApplicationStatus::Accepted->value)->limit(2)->get();
            abort_unless($selected->count() === 1, 409);
            $proposal = $selected->sole();
            abort_unless($proposal->editor_id === $work->editor_id && $proposal->proposed_price === $work->agreed_price, 409);
            $terms += ['application_id' => $proposal->id, 'cover_message' => $proposal->cover_message,
                'estimated_delivery_days' => $proposal->estimated_delivery_days];
        }

        return $terms;
    }

    public function confirm(User $actor, string $id, array $input): array
    {
        return $this->access->locked($this->access->participant($actor, $id), $actor,
            function (WorkItem $work, array $users, User $current) use ($input) {
                $this->access->activePair($work, $users);
                abort_unless($work->status === WorkStatus::PendingAgreement && $work->agreed_at === null
                    && $work->started_at === null && $work->agreed_price !== null, 409);
                $fingerprint = self::fingerprint($this->forWork($work));
                abort_unless(hash_equals($fingerprint, $input['terms_fingerprint']), 409);

                if ($current->role === UserRole::Creator) {
                    $receipt = Crypt::encryptString(json_encode([
                        'purpose' => 'frame.creator_assent', 'fingerprint' => $fingerprint,
                        'expires_at' => now()->addHour()->timestamp,
                    ], JSON_THROW_ON_ERROR));

                    return ['creator_receipt' => $receipt, 'agreed_at' => null];
                }

                abort_unless(isset($input['creator_receipt']), 409);
                try {
                    $receipt = json_decode(Crypt::decryptString($input['creator_receipt']), true, flags: JSON_THROW_ON_ERROR);
                } catch (DecryptException|\JsonException) {
                    abort(409);
                }
                abort_unless(($receipt['purpose'] ?? null) === 'frame.creator_assent'
                    && ($receipt['fingerprint'] ?? null) === $fingerprint && ($receipt['expires_at'] ?? 0) > now()->timestamp, 409);
                $work->agreed_at = now();
                $work->save();

                return ['agreed_at' => $work->agreed_at->toISOString()];
            },
        );
    }
}
