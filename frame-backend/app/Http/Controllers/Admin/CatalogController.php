<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentType;
use App\Models\EditingTool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CatalogController
{
    public function index(Request $request, string $catalog): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'required', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'between:1,21474836'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'archived' => ['sometimes', Rule::in(['0', '1'])],
        ]);
        $query = $this->catalog($catalog);
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.addcslashes($filters['search'], '\\%_').'%');
        }
        if (($filters['archived'] ?? null) === '1') {
            $query->onlyTrashed();
        }

        $page = $query->orderBy('name')->orderBy('id')->simplePaginate((int) ($filters['per_page'] ?? 20));

        return response()->json(['data' => array_map($this->fields(...), $page->items()),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'has_more' => $page->hasMorePages()]]);
    }

    public function show(string $catalog, string $id): JsonResponse
    {
        return response()->json(['data' => $this->fields($this->catalog($catalog)->withTrashed()->findOrFail($id))]);
    }

    public function store(Request $request, string $catalog): JsonResponse
    {
        $item = $this->catalog($catalog)->make();
        $this->save($request, $item);

        return response()->json(['data' => $this->fields($item->refresh())], 201);
    }

    public function update(Request $request, string $catalog, string $id): JsonResponse
    {
        $query = $this->catalog($catalog);
        $item = $query->getModel()->getConnection()->transaction(function () use ($request, $query, $id) {
            $item = $query->lockForUpdate()->findOrFail($id);
            $this->save($request, $item);

            return $item;
        });

        return response()->json(['data' => $this->fields($item)]);
    }

    public function destroy(string $catalog, string $id): Response
    {
        $query = $this->catalog($catalog);
        $db = $query->getModel()->getConnection();
        $db->transaction(function () use ($query, $db, $id) {
            $item = $query->lockForUpdate()->findOrFail($id);
            // Include archived rows and pivots. They still carry historical catalog references.
            $references = $item instanceof ContentType
                ? ['user_content_types', 'portfolio_projects', 'editor_services', 'work_items']
                : ['editor_tools', 'work_items'];
            $column = $item instanceof ContentType ? 'content_type_id' : 'editing_tool_id';
            foreach ($references as $table) {
                abort_if($db->table($table)->where($column, $item->getKey())->exists(), 409, 'This catalog entry is referenced.');
            }
            $item->delete();
        });

        return response()->noContent();
    }

    private function fields(Model $item): array
    {
        return $item->only(['id', 'name', 'deleted_at']);
    }

    private function catalog(string $catalog): Builder
    {
        return match ($catalog) {
            'content-types' => ContentType::query(),
            'editing-tools' => EditingTool::query(),
        };
    }

    private function save(Request $request, Model $item): void
    {
        $name = Rule::unique($item->getTable(), 'name');
        if ($item->exists) {
            $name->ignore($item);
        }
        $data = $request->validate(['name' => ['required', 'string', 'max:100', $name]]);
        try {
            $item->fill($data)->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'The name has already been taken.']);
        }
    }
}
