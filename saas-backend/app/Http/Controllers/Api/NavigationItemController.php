<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NavigationItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $items = NavigationItem::query()
            ->whereNull('parent_id')
            ->where('type', 'page')
            ->with(['children.actions', 'children.children.actions', 'actions'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->validatePayload($request);
        $this->validateTreeRules($data);

        $item = NavigationItem::query()->create($data);

        return response()->json(['data' => $item->fresh()], 201);
    }

    public function show(Request $request, NavigationItem $navigationItem): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $navigationItem->load(['children.actions', 'actions']),
        ]);
    }

    public function update(Request $request, NavigationItem $navigationItem): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->validatePayload($request, $navigationItem);
        $this->validateTreeRules($data, $navigationItem);

        $navigationItem->update($data);

        return response()->json(['data' => $navigationItem->fresh()]);
    }

    public function destroy(Request $request, NavigationItem $navigationItem): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($navigationItem->allChildren()->exists() && !$request->boolean('force')) {
            throw ValidationException::withMessages([
                'id' => ['Cannot delete a navigation item with children unless force=true.'],
            ]);
        }

        $navigationItem->delete();

        return response()->json(['message' => 'Navigation item deleted successfully']);
    }

    public function reorder(Request $request): JsonResponse
    {
        if (!$this->isPlatformAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:navigation_items,id'],
            'items.*.parent_id' => ['nullable', 'exists:navigation_items,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                $navigationItem = NavigationItem::query()->findOrFail($item['id']);
                $nextData = [
                    'parent_id' => $item['parent_id'] ?? null,
                    'sort_order' => $item['sort_order'],
                ];

                $this->validateTreeRules([
                    ...$navigationItem->only(['type', 'title', 'slug', 'route', 'icon', 'permission_name', 'is_active', 'meta']),
                    ...$nextData,
                ], $navigationItem);

                $navigationItem->update($nextData);
            }
        });

        return response()->json(['message' => 'Navigation order updated successfully']);
    }

    private function validatePayload(Request $request, ?NavigationItem $item = null): array
    {
        $id = $item?->id;

        return $request->validate([
            'parent_id' => ['nullable', 'exists:navigation_items,id'],
            'type' => ['required', Rule::in(['page', 'action'])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('navigation_items', 'slug')->ignore($id)],
            'route' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'permission_name' => ['nullable', 'exists:permissions,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validateTreeRules(array $data, ?NavigationItem $item = null): void
    {
        $type = $data['type'] ?? $item?->type;
        $parentId = $data['parent_id'] ?? null;

        if ($type === 'action' && !$parentId) {
            throw ValidationException::withMessages([
                'parent_id' => ['Action navigation items must belong to a page.'],
            ]);
        }

        if ($parentId) {
            $parent = NavigationItem::query()->find($parentId);

            if (!$parent) {
                throw ValidationException::withMessages(['parent_id' => ['Parent navigation item not found.']]);
            }

            if ($parent->type === 'action') {
                throw ValidationException::withMessages([
                    'parent_id' => ['Actions cannot have child pages or child actions.'],
                ]);
            }

            if ($item && $this->wouldCreateCycle($item, $parent)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Navigation parent cannot create a circular tree.'],
                ]);
            }
        }
    }

    private function wouldCreateCycle(NavigationItem $item, NavigationItem $parent): bool
    {
        if ($item->id === $parent->id) {
            return true;
        }

        $cursor = $parent;
        while ($cursor->parent_id) {
            if ($cursor->parent_id === $item->id) {
                return true;
            }
            $cursor = NavigationItem::query()->find($cursor->parent_id);
            if (!$cursor) {
                break;
            }
        }

        return false;
    }

    private function isPlatformAdmin(Request $request): bool
    {
        return $request->attributes->get('jwt_role') === 'platform_admin';
    }
}
