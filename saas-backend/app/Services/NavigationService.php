<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NavigationService
{
    /**
     * Get dynamic navigation tree for a user in the active organization.
     */
    public function getNavigation(User $user, Organization $organization): array
    {
        $items = NavigationItem::query()
            ->whereNull('parent_id')
            ->where('type', 'page')
            ->where('is_active', true)
            ->with(['children.actions', 'children.children.actions', 'actions'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return $this->filterAndTransform($items, $user);
    }

    /**
     * Legacy API: role-only navigation cannot be permission-filtered safely.
     */
    public function getNavigationByRole(string $role): array
    {
        return [];
    }

    public function getHomeRoute(string $role): string
    {
        return config("navigation.home_routes.{$role}", '/');
    }

    public function getLayoutType(string $role): string
    {
        return config("navigation.layout_types.{$role}", 'home');
    }

    public function seedOrganizationNavigation(Organization $organization): void
    {
        // Dynamic navigation is global and permission-filtered per user/context.
    }

    /**
     * @param Collection<int, NavigationItem> $items
     */
    private function filterAndTransform(Collection $items, User $user): array
    {
        return $items
            ->map(fn (NavigationItem $item) => $this->transformVisibleItem($item, $user))
            ->filter()
            ->values()
            ->all();
    }

    private function transformVisibleItem(NavigationItem $item, User $user): ?array
    {
        if (!$this->canAccess($user, $item->permission_name)) {
            return null;
        }

        $children = $this->filterAndTransform($item->children, $user);
        $actions = $item->actions
            ->filter(fn (NavigationItem $action) => $this->canAccess($user, $action->permission_name))
            ->map(fn (NavigationItem $action) => [
                'id' => $action->id,
                'title' => $action->title,
                'slug' => $action->slug,
                'type' => 'action',
                'permission' => $action->permission_name,
                'meta' => $action->meta ?? [],
            ])
            ->values()
            ->all();

        return [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'type' => 'page',
            'to' => $item->route,
            'icon' => $item->icon,
            'permission' => $item->permission_name,
            'children' => $children,
            'actions' => $actions,
            'meta' => $item->meta ?? [],
        ];
    }

    private function canAccess(User $user, ?string $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        $organizationId = app()->bound('organization_id') ? app('organization_id') : null;

        if (!$organizationId) {
            return false;
        }

        $roleNames = DB::table('organization_users')
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->pluck('role')
            ->filter()
            ->all();

        if (empty($roleNames)) {
            return false;
        }

        return Role::query()
            ->where('organization_id', $organizationId)
            ->whereIn('name', $roleNames)
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }
}
