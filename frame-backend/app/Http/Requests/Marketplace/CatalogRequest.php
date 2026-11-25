<?php

namespace App\Http\Requests\Marketplace;

use App\Enums\EditorExperienceLevel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

final class CatalogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'between:1,21474836'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort' => ['sometimes', Rule::in(['newest', 'oldest'])],
            'content_type_id' => ['sometimes', 'uuid'],
            'editing_tool_id' => ['sometimes', 'uuid'],
            'experience_level' => ['sometimes', Rule::enum(EditorExperienceLevel::class)],
            'professional_title' => ['sometimes', 'string', 'max:100'],
            'q' => ['sometimes', 'string', 'max:100'],
            'open_to_work' => ['sometimes', Rule::in(['0', '1'])],
            'recorded_approval' => ['sometimes', Rule::in(['1'])],
            'budget_at_least' => ['sometimes', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'budget_at_most' => ['sometimes', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'deadline_before' => ['sometimes', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }

    public function page(Builder|Relation $query, string $resource, ?string $order = 'created_at'): JsonResponse
    {
        $direction = $this->validated('sort') === 'oldest' ? 'asc' : 'desc';
        if ($order !== null) {
            $query->orderBy($query->getModel()->qualifyColumn($order), $direction);
        }

        $page = $query->orderBy($query->getModel()->qualifyColumn('id'), $direction)
            ->simplePaginate((int) $this->validated('per_page', 20), ['*'], 'page', (int) $this->validated('page', 1));

        return response()->json([
            'data' => $resource::collection($page->items())->resolve($this),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'has_more' => $page->hasMorePages()],
        ]);
    }
}
