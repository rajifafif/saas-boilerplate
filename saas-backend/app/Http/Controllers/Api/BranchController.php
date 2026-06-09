<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $branches = Branch::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name');

        if ($request->has('sync')) {
            $branches->where('updated_at', '>', $request->sync);
            return response()->json($branches->get());
        }

        return response()->json($branches->paginate($request->perPage ?? 15));
    }

    public function store(Request $request)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            ...$validated,
            'organization_id' => $organizationId,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->findOrFail($id);

        return response()->json(['data' => $branch]);
    }

    public function update(Request $request, string $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->ignore($branch->id)
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $branch->refresh(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->findOrFail($id);

        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully']);
    }
}
