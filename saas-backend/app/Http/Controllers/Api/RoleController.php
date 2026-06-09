<?php


namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAccessResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $roles = Role::where('organization_id', $organizationId)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->with('permissions'); // Eager load permissions

        if ($request->has('sync')) {
            $roles->where('updated_at', '>', $request->sync);
            return response()->json($roles->get());
        }

        return response()->json($roles->paginate($request->perPage ?? 15));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name', // Validate by name as Spatie uses names, or id if passed
        ]);

        // Check uniqueness within organization
        $exists = Role::where('organization_id', $organizationId)
            ->where('name', $request->name)
            ->where('guard_name', 'web') // Default guard
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Role with this name already exists in your organization.'], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
                'organization_id' => $organizationId
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'message' => 'Role created successfully',
                'data' => $role->load('permissions')
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        $role = Role::where('organization_id', $organizationId)->findOrFail($id);

        return response()->json([
            'data' => $role->load('permissions')
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json(['message' => 'Organization context required'], 403);
        }

        $role = Role::where('organization_id', $organizationId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // Check uniqueness if name changed
        if ($request->name !== $role->name) {
            $exists = Role::where('organization_id', $organizationId)
                ->where('name', $request->name)
                ->where('guard_name', 'web')
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Role with this name already exists.'], 422);
            }
        }

        try {
            DB::beginTransaction();

            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions')
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, $id)
    {
        $organizationId = $request->attributes->get('jwt_org_id');

        $role = Role::where('organization_id', $organizationId)->findOrFail($id);

        // Prevent deleting system roles if any logic exists (optional)
        // e.g. if ($role->name === 'admin') return error...

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getAllPermissions(Request $request)
    {
        // Return all available permissions
        // We might want to filter permissions relevant to the organization or return all
        // Usually permissions are system-defined.

        $permissions = Permission::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            });

        return response()->json($permissions->get());
    }

    // Kept for backward compatibility if needed, else used by route aliases
    public function getAllRoles(Request $request)
    {
        return $this->index($request);
    }
}
