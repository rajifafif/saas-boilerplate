<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Get navigation and layout configuration for the authenticated user.
     *
     * Returns navigation items, home route, and layout type based on user's role.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get role from JWT attributes (set by middleware) or fallback to 'member'
        $role = $request->attributes->get('jwt_role') ?? 'member';

        // If we have a user and organization context, get full navigation with permissions
        $organizationId = $request->attributes->get('organization_id');

        if ($user && $organizationId) {
            $organization = \App\Models\Organization::find($organizationId);
            if ($organization) {
                $navigation = $this->navigationService->getNavigation($user, $organization);
            } else {
                $navigation = $this->navigationService->getNavigationByRole($role);
            }
        } else {
            $navigation = $this->navigationService->getNavigationByRole($role);
        }

        return response()->json([
            'navigation' => $navigation,
            'home_route' => $this->navigationService->getHomeRoute($role),
            'layout_type' => $this->navigationService->getLayoutType($role),
            'role' => $role,
        ]);
    }
}
