<?php

namespace App\Http\Requests\Verification;

use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class VerificationUploadRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(['professional_evidence', 'identity_document'])],
            'document' => ['bail', 'required', 'file', 'max:5120', function (string $attribute, UploadedFile $file, Closure $fail) {
                $mime = match (strtolower($file->getClientOriginalExtension())) {
                    'pdf' => 'application/pdf',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    default => null,
                };
                if ($mime === null || (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) !== $mime) {
                    $fail('Upload a PDF, JPEG, or PNG with a matching extension.');
                }
            }],
        ];
    }
}
