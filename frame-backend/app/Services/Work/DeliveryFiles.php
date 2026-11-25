<?php

namespace App\Services\Work;

use App\Models\Deliverable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DeliveryFiles
{
    public function store(UploadedFile $file): string
    {
        $id = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = Storage::disk('local')->putFileAs('work-deliverables', $file, $id, ['visibility' => 'private']);
        abort_unless($path !== false, 503);

        return $path;
    }

    public function discard(string $path): void
    {
        if (! Storage::disk('local')->delete($path)) {
            report(new RuntimeException('Failed to clean up a deliverable upload.'));
        }
    }

    public static function token(Deliverable $delivery): ?string
    {
        if ($delivery->submitted_at === null || ! self::managedPath($delivery->storage_key)) {
            return null;
        }

        return hash('sha256', $delivery->id.':'.$delivery->storage_key);
    }

    public function exists(Deliverable $delivery): bool
    {
        return self::token($delivery) !== null && Storage::disk('local')->exists($delivery->storage_key);
    }

    public function download(Deliverable $delivery): StreamedResponse
    {
        abort_unless($this->exists($delivery), 404);
        $extension = pathinfo($delivery->storage_key, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($delivery->storage_key, 'deliverable.'.$extension, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private static function managedPath(?string $path): bool
    {
        return preg_match('~^work-deliverables/[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}\.(mp4|mov|webm)$~D', $path ?? '') === 1;
    }
}
