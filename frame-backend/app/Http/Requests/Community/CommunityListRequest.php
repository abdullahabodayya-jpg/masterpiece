<?php

namespace App\Http\Requests\Community;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

final class CommunityListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort' => ['sometimes', Rule::in(['newest', 'oldest'])],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }

    public function page(Builder|Relation $query, string $resource, string $dateColumn, string $idColumn): JsonResponse
    {
        $direction = $this->validated('sort') === 'oldest' ? 'asc' : 'desc';
        $page = $query->orderBy($dateColumn, $direction)
            ->orderBy($idColumn, $direction)
            ->simplePaginate(
                (int) $this->validated('per_page', 20),
                ['*'],
                'page',
                (int) $this->validated('page', 1),
            );

        return response()->json([
            'data' => $resource::collection($page->items())->resolve($this),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }
}
