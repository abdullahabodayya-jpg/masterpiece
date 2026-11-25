<?php

namespace App\Http\Requests\Work;

use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Closure;
use finfo;
use Illuminate\Http\UploadedFile;

final class DeliveryRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $token = ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'];

        return match ($this->route()->getActionMethod()) {
            'store', 'update' => [
                'title' => [$this->isMethod('PATCH') ? 'sometimes' : 'required', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
                'due_at' => ['sometimes', 'nullable', 'date', 'after:now', 'before:2038-01-19 03:14:07 UTC'],
            ],
            'submit' => ['file' => ['bail', 'required', 'file', 'max:20480', $this->videoValidator()]],
            'review', 'accept' => [
                'submission_token' => $token,
                'review_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            ],
            'requestRevision' => [
                'submission_token' => $token,
                'reason' => ['required', 'string', 'max:255'],
                'details' => ['sometimes', 'nullable', 'string', 'max:5000'],
            ],
            default => [],
        };
    }

    private function videoValidator(): Closure
    {
        return function (string $attribute, UploadedFile $value, Closure $fail) {
            $expected = match (strtolower($value->getClientOriginalExtension())) {
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',
                default => null,
            };

            if ($expected === null || (new finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath()) !== $expected) {
                $fail('Upload an MP4, MOV, or WebM video with a matching extension.');
            }
        };
    }
}
