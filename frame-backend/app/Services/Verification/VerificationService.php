<?php

namespace App\Services\Verification;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\EditorVerificationRequest;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class VerificationService
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function editor(User $actor): void
    {
        abort_unless(! $actor->trashed() && $actor->role === UserRole::Editor
            && in_array($actor->status, [AccountStatus::Active, AccountStatus::PendingVerification], true), 403);
    }

    public function reviewer(User $actor): void
    {
        abort_unless(! $actor->trashed() && $actor->role === UserRole::Admin && $actor->status === AccountStatus::Active, 403);
    }

    public function current(User $editor): ?string
    {
        return $this->access->latestDecision($editor->getConnection()->query())->where('editor_id', $editor->id)->value('status');
    }

    public function submit(User $editor, string $type, UploadedFile $document): EditorVerificationRequest
    {
        $this->editor($editor);
        $disk = Storage::disk('local');
        $name = Str::uuid().'.'.strtolower($document->getClientOriginalExtension());
        $path = 'editor-verification/'.$name;
        abort_unless($disk->putFileAs('editor-verification', $document, $name, ['visibility' => 'private']) === $path, 503);

        try {
            return $this->access->lockAccounts($editor, [], function (User $owner) use ($type, $name, $path) {
                $this->editor($owner);
                abort_if($owner->editorVerificationRequests()->whereIn('status', ['pending', 'under_review'])->exists()
                    || $this->current($owner) === VerificationStatus::Approved->value, 409);

                $request = $owner->editorVerificationRequests()->make();
                $request->status = VerificationStatus::Pending;
                $request->document_type = $type;
                $request->document_file_name = $name;
                $request->document_storage_key = $path;
                $request->submitted_at = now();
                $request->save();

                return $request;
            });
        } catch (Throwable $exception) {
            if (! $disk->delete($path)) {
                report(new \RuntimeException('Verification upload cleanup failed.'));
            }
            throw $exception;
        }
    }

    public function decide(User $admin, string $id, VerificationStatus $decision, ?string $notes): EditorVerificationRequest
    {
        $this->reviewer($admin);
        abort_unless(in_array($decision, [VerificationStatus::Approved, VerificationStatus::Rejected], true), 409);
        $candidate = EditorVerificationRequest::query()->findOrFail($id);
        abort_if($candidate->editor_id === $admin->id, 403);

        return $this->access->lockAccounts($admin, [$candidate->editor_id], function (User $reviewer, array $users) use ($candidate, $decision, $notes) {
            $this->reviewer($reviewer);
            abort_unless($users[$candidate->editor_id]->role === UserRole::Editor, 409);
            $request = EditorVerificationRequest::query()->lockForUpdate()->findOrFail($candidate->id);
            abort_unless($request->status === VerificationStatus::Pending, 409);
            if ($decision === VerificationStatus::Approved) {
                abort_unless($this->documentExists($request), 409);
            }
            $request->status = $decision;
            $request->reviewed_by_user_id = $reviewer->id;
            $request->decided_at = now();
            $request->decision_notes = $notes;
            $request->save();

            return $request;
        });
    }

    public function download(EditorVerificationRequest $request): StreamedResponse
    {
        abort_unless($this->documentExists($request), 404);

        return Storage::disk('local')->download($request->document_storage_key, 'verification-document.'.pathinfo($request->document_storage_key, PATHINFO_EXTENSION), [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function documentExists(EditorVerificationRequest $request): bool
    {
        return preg_match('~^editor-verification/[0-9a-f-]{36}\.(pdf|png|jpg|jpeg)$~D', $request->document_storage_key ?? '') === 1
            && Storage::disk('local')->exists($request->document_storage_key);
    }
}
