<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    /**
     * List all organizations the authenticated user belongs to.
     * 
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->withPivot('role', 'is_default', 'joined_at')
            ->with('branches:id,organization_id,name,code,is_active')
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'type' => $org->type,
                    'role' => $org->pivot->role,
                    'is_default' => $org->pivot->is_default,
                    'joined_at' => $org->pivot->joined_at,
                    'branches' => $org->branches,
                ];
            });

        // Get current org from JWT (stateless)
        $currentOrgId = $request->attributes->get('jwt_org_id');
        $currentRole = $request->attributes->get('jwt_role');

        return response()->json([
            'data' => $organizations,
            'current_organization_id' => $currentOrgId,
            'current_role' => $currentRole,
        ]);
    }

    /**
     * Get details of a specific organization.
     * 
     * @param Organization $organization
     * @return JsonResponse
     */
    public function show(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        // Check if user belongs to this organization
        $membership = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'You do not have access to this organization.',
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'role' => $membership->pivot->role,
                'is_default' => $membership->pivot->is_default,
                'joined_at' => $membership->pivot->joined_at,
                'branches' => $organization->branches()
                    ->select('id', 'name', 'code', 'is_active')
                    ->get(),
            ],
        ]);
    }

    /**
     * Get organization by slug (PUBLIC - for subdomain resolution).
     * 
     * @param string $slug
     * @return JsonResponse
     */
    public function bySlug(string $slug): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'allow_public_registration' => $organization->allow_public_registration ?? true,
            ],
        ]);
    }

    /**
     * Get the currently active organization (from JWT).
     * 
     * @return JsonResponse
     */
    public function current(Request $request): JsonResponse
    {
        // Get org from JWT context (stateless)
        $organizationId = $request->attributes->get('jwt_org_id');

        if (!$organizationId) {
            return response()->json([
                'message' => 'No organization is currently selected.',
                'data' => null,
            ], 200);
        }

        $organization = Organization::with('branches:id,organization_id,name,code,is_active')
            ->find($organizationId);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        $user = $request->user();
        $membership = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();

        return response()->json([
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'role' => $membership?->pivot->role ?? $request->attributes->get('jwt_role'),
                'branches' => $organization->branches,
            ],
            'branch_id' => $request->attributes->get('branch_id'),
        ]);
    }

    /**
     * Switch to a different organization.
     * 
     * Returns new access and refresh tokens with the new org context.
     * This makes the switch completely stateless - the new org context
     * is embedded in the tokens.
     * 
     * @param Request $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function switch(Request $request, Organization $organization): JsonResponse
    {
        // Delegate to AuthController's switchOrganization method
        // which handles token re-issue
        $authController = app(AuthController::class);
        return $authController->switchOrganization($request, $organization);
    }

    /**
     * Set the default organization for the authenticated user.
     * 
     * @param Request $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function setDefault(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        // Verify user belongs to this organization
        $membership = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'You are not a member of this organization.',
            ], 403);
        }

        // Clear existing default
        $user->organizations()->updateExistingPivot(
            $user->organizations->pluck('id')->toArray(),
            ['is_default' => false]
        );

        // Set new default
        $user->organizations()->updateExistingPivot(
            $organization->id,
            ['is_default' => true]
        );

        return response()->json([
            'message' => $organization->name . ' is now your default organization.',
        ]);
    }
}

