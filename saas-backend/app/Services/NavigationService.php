<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;

class NavigationService
{
    /**
     * Get navigation for a user based on their role in the organization.
     */
    public function getNavigation(User $user, Organization $organization): array
    {
        // Get user's role in this organization
        $membership = $user->organizations()
            ->where('organization_id', $organization->id)
            ->first();

        $role = $membership?->pivot?->role ?? 'member';

        // Map role to navigation type
        $navType = config("navigation.role_mapping.{$role}", 'member');

        // Get navigation for this role
        $navigation = config("navigation.{$navType}", []);

        // Filter by permissions if user has them
        return $this->filterByPermissions($navigation, $user, $organization);
    }

    /**
     * Get navigation by role directly (for API).
     */
    public function getNavigationByRole(string $role): array
    {
        $navType = config("navigation.role_mapping.{$role}", 'member');
        return config("navigation.{$navType}", []);
    }

    /**
     * Get home route for a role.
     */
    public function getHomeRoute(string $role): string
    {
        return config("navigation.home_routes.{$role}", '/');
    }

    /**
     * Filter navigation items by user permissions.
     */
    protected function filterByPermissions(array $navigation, User $user, Organization $organization): array
    {
        $filtered = [];

        foreach ($navigation as $section) {
            // Check section-level permissions
            if (isset($section['permissions'])) {
                if (!$this->userHasAnyPermission($user, $section['permissions'])) {
                    continue;
                }
            }

            $validItems = [];
            if (isset($section['items'])) {
                foreach ($section['items'] as $item) {
                    // Check item-level permissions
                    if (isset($item['permissions'])) {
                        if (!$this->userHasAnyPermission($user, $item['permissions'])) {
                            continue;
                        }
                    }
                    $validItems[] = $item;
                }
            }

            if (!empty($validItems) || !isset($section['items'])) {
                $section['items'] = $validItems;
                $filtered[] = $section;
            }
        }

        return $filtered;
    }

    /**
     * Check if user has any of the given permissions.
     */
    protected function userHasAnyPermission(User $user, array $permissions): bool
    {
        // Try to check permissions via Spatie
        try {
            return $user->hasAnyPermission($permissions);
        } catch (\Exception $e) {
            // If permission check fails, allow access (permissions not set up)
            return true;
        }
    }

    /**
     * Initialize default navigation for an organization (legacy support).
     */
    public function seedOrganizationNavigation(Organization $organization): void
    {
        // Navigation is now role-based from config, no need to seed per-org
        // This method kept for backward compatibility
    }

    /**
     * Get layout type for a role.
     * 
     * @return string 'admin' for sidebar layout, 'home' for card-based layout
     */
    public function getLayoutType(string $role): string
    {
        return config("navigation.layout_types.{$role}", 'home');
    }
}

