<?php

namespace App\Http\Requests\Messaging;

use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Closure;

final class MessagingRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'direct' => [
                'recipient_id' => ['required', 'uuid'],
                'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            'project' => ['title' => ['required', 'string', 'max:255']],
            'send' => ['message_body' => ['bail', 'required', 'string', 'max:5000', function (string $attribute, string $value, Closure $fail) {
                if (preg_match('/[^\p{Z}\p{Cf}\s]/u', $value) !== 1) {
                    $fail('Enter a message containing visible text.');
                }
            }]],
            'read' => ['message_id' => ['required', 'uuid']],
        };
    }
}
