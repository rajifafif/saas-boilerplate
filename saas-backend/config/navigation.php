<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role-Based Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Navigation menus organized by user role. The active boilerplate surface is
    | domain-neutral SaaS: dashboard, organizations, branches, users/team,
    | roles/permissions, profile/settings, and billing/subscriptions.
    |
    | Roles: owner, admin, staff, member
    |
    */

    'admin' => [
        [
            'subheader' => 'Workspace',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'to' => '/manage',
                    'icon' => 'tabler-dashboard',
                    'permissions' => ['access_dashboard'],
                ],
            ],
        ],
        [
            'subheader' => 'Organization',
            'items' => [
                [
                    'title' => 'Organizations',
                    'to' => '/manage/organizations',
                    'icon' => 'tabler-building',
                    'permissions' => ['view_organization'],
                ],
                [
                    'title' => 'Branches',
                    'to' => '/manage/branches',
                    'icon' => 'tabler-map-pin',
                    'permissions' => ['view_branches'],
                ],
            ],
        ],
        [
            'subheader' => 'Team',
            'items' => [
                [
                    'title' => 'Users',
                    'to' => '/manage/users',
                    'icon' => 'tabler-users',
                    'permissions' => ['view_users'],
                ],
                [
                    'title' => 'Roles & Permissions',
                    'to' => '/manage/roles',
                    'icon' => 'tabler-shield-lock',
                    'permissions' => ['view_roles', 'manage_permissions'],
                ],
            ],
        ],
        [
            'subheader' => 'Billing',
            'items' => [
                [
                    'title' => 'Subscriptions',
                    'to' => '/manage/subscriptions',
                    'icon' => 'tabler-receipt',
                    'permissions' => ['manage_billing'],
                ],
            ],
        ],
        [
            'subheader' => 'Settings',
            'items' => [
                [
                    'title' => 'Account Settings',
                    'to' => '/manage/settings/account',
                    'icon' => 'tabler-settings',
                    'permissions' => ['manage_profile'],
                ],
            ],
        ],
    ],

    'member' => [
        [
            'subheader' => 'Workspace',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'to' => '/',
                    'icon' => 'tabler-home',
                    'permissions' => ['access_dashboard'],
                ],
                [
                    'title' => 'Profile',
                    'to' => '/profile',
                    'icon' => 'tabler-user',
                    'permissions' => ['manage_profile'],
                ],
            ],
        ],
    ],

    'role_mapping' => [
        'platform_admin' => 'admin',
        'owner' => 'admin',
        'organization_manager' => 'admin',
        'admin' => 'admin',
        'staff' => 'admin',
        'manager' => 'admin',
        'member' => 'member',
    ],

    'home_routes' => [
        'platform_admin' => '/manage',
        'owner' => '/manage',
        'organization_manager' => '/manage',
        'admin' => '/manage',
        'staff' => '/manage',
        'manager' => '/manage',
        'member' => '/',
    ],

    'layout_types' => [
        'platform_admin' => 'admin',
        'owner' => 'admin',
        'organization_manager' => 'admin',
        'admin' => 'admin',
        'staff' => 'admin',
        'manager' => 'admin',
        'member' => 'home',
    ],
];