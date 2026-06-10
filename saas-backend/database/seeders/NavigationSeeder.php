<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'Dashboard',
                'slug' => 'dashboard',
                'route' => '/manage',
                'icon' => 'tabler-dashboard',
                'permission' => 'dashboard.view_stats',
                'sort' => 10,
            ],
            [
                'title' => 'Organization',
                'slug' => 'organization',
                'route' => '/manage/organizations',
                'icon' => 'tabler-building',
                'permission' => 'organization.view',
                'sort' => 20,
                'children' => [
                    [
                        'title' => 'Organizations',
                        'slug' => 'organizations',
                        'route' => '/manage/organizations',
                        'icon' => 'tabler-building',
                        'permission' => 'organization.view',
                        'sort' => 10,
                        'actions' => [
                            ['title' => 'Update Organization', 'slug' => 'organizations.update', 'permission' => 'organization.update', 'sort' => 10],
                        ],
                    ],
                    [
                        'title' => 'Branches',
                        'slug' => 'branches',
                        'route' => '/manage/branches',
                        'icon' => 'tabler-map-pin',
                        'permission' => 'branch.view',
                        'sort' => 20,
                        'actions' => [
                            ['title' => 'Create Branch', 'slug' => 'branches.create', 'permission' => 'branch.create', 'sort' => 10],
                            ['title' => 'Update Branch', 'slug' => 'branches.update', 'permission' => 'branch.update', 'sort' => 20],
                            ['title' => 'Delete Branch', 'slug' => 'branches.delete', 'permission' => 'branch.delete', 'sort' => 30],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Team',
                'slug' => 'team',
                'route' => '/manage/users',
                'icon' => 'tabler-users',
                'permission' => 'staff.view',
                'sort' => 30,
                'children' => [
                    [
                        'title' => 'Users',
                        'slug' => 'users',
                        'route' => '/manage/users',
                        'icon' => 'tabler-users',
                        'permission' => 'staff.view',
                        'sort' => 10,
                        'actions' => [
                            ['title' => 'Create User', 'slug' => 'users.create', 'permission' => 'staff.create', 'sort' => 10],
                            ['title' => 'Update User', 'slug' => 'users.update', 'permission' => 'staff.update', 'sort' => 20],
                            ['title' => 'Delete User', 'slug' => 'users.delete', 'permission' => 'staff.delete', 'sort' => 30],
                            ['title' => 'Reset User Password', 'slug' => 'users.reset-password', 'permission' => 'staff.reset_password', 'sort' => 40],
                        ],
                    ],
                    [
                        'title' => 'Roles & Permissions',
                        'slug' => 'roles',
                        'route' => '/manage/roles',
                        'icon' => 'tabler-shield-lock',
                        'permission' => 'role.view',
                        'sort' => 20,
                        'actions' => [
                            ['title' => 'Create Role', 'slug' => 'roles.create', 'permission' => 'role.create', 'sort' => 10],
                            ['title' => 'Update Role', 'slug' => 'roles.update', 'permission' => 'role.update', 'sort' => 20],
                            ['title' => 'Delete Role', 'slug' => 'roles.delete', 'permission' => 'role.delete', 'sort' => 30],
                            ['title' => 'Assign Role', 'slug' => 'roles.assign', 'permission' => 'role.assign', 'sort' => 40],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Platform',
                'slug' => 'platform',
                'route' => '/manage/menu-builder',
                'icon' => 'tabler-adjustments',
                'permission' => 'navigation.view',
                'sort' => 40,
                'children' => [
                    [
                        'title' => 'Menu Builder',
                        'slug' => 'menu-builder',
                        'route' => '/manage/menu-builder',
                        'icon' => 'tabler-menu-2',
                        'permission' => 'navigation.view',
                        'sort' => 10,
                        'actions' => [
                            ['title' => 'Create Menu Item', 'slug' => 'menu-builder.create', 'permission' => 'navigation.create', 'sort' => 10],
                            ['title' => 'Update Menu Item', 'slug' => 'menu-builder.update', 'permission' => 'navigation.update', 'sort' => 20],
                            ['title' => 'Delete Menu Item', 'slug' => 'menu-builder.delete', 'permission' => 'navigation.delete', 'sort' => 30],
                            ['title' => 'Reorder Menu Item', 'slug' => 'menu-builder.reorder', 'permission' => 'navigation.reorder', 'sort' => 40],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Billing',
                'slug' => 'billing',
                'route' => '/manage/subscriptions',
                'icon' => 'tabler-receipt',
                'permission' => 'organization.manage_billing',
                'sort' => 50,
            ],
            [
                'title' => 'Profile',
                'slug' => 'profile',
                'route' => '/profile',
                'icon' => 'tabler-user',
                'permission' => 'organization.view',
                'sort' => 50,
            ],
        ];

        foreach ($items as $item) {
            $this->upsertItem($item);
        }
    }

    private function upsertItem(array $item, ?NavigationItem $parent = null, string $type = 'page'): NavigationItem
    {
        if (!empty($item['permission'])) {
            Permission::firstOrCreate(['name' => $item['permission'], 'guard_name' => 'web']);
        }

        $navigationItem = NavigationItem::query()->updateOrCreate(
            ['slug' => $item['slug']],
            [
                'parent_id' => $parent?->id,
                'type' => $type,
                'title' => $item['title'],
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? null,
                'permission_name' => $item['permission'] ?? null,
                'sort_order' => $item['sort'] ?? 0,
                'is_active' => $item['is_active'] ?? true,
                'meta' => $item['meta'] ?? null,
            ]
        );

        foreach ($item['children'] ?? [] as $child) {
            $this->upsertItem($child, $navigationItem, 'page');
        }

        foreach ($item['actions'] ?? [] as $action) {
            $this->upsertItem($action, $navigationItem, 'action');
        }

        return $navigationItem;
    }
}
