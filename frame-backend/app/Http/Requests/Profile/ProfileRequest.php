<?php

namespace App\Http\Requests\Profile;

use App\Enums\EditorExperienceLevel;
use App\Enums\UserRole;
use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Illuminate\Validation\Rule;

final class ProfileRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $common = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'content_type_ids' => ['sometimes', 'array', 'list', 'max:50'],
            'content_type_ids.*' => ['required', 'uuid', 'distinct:ignore_case', Rule::exists('content_types', 'id')->whereNull('deleted_at')],
        ];

        return $this->user('sanctum')->role === UserRole::Editor ? $common + [
            'professional_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'experience_level' => ['sometimes', 'nullable', Rule::enum(EditorExperienceLevel::class)],
            'years_of_experience' => ['sometimes', 'nullable', 'integer', 'between:0,65535'],
            'is_open_to_work' => ['sometimes', 'boolean'],
            'editing_tool_ids' => ['sometimes', 'array', 'list', 'max:50'],
            'editing_tool_ids.*' => ['required', 'uuid', 'distinct:ignore_case', Rule::exists('editing_tools', 'id')->whereNull('deleted_at')],
        ] : $common + [
            'creator_niche' => ['sometimes', 'nullable', 'string', 'max:100'],
            'audience_size' => ['sometimes', 'nullable', 'integer', 'between:0,4294967295'],
        ];
    }
}
