<?php

namespace App\Services;

class PermissionCatalog
{
    public const ROLES = [
        'Owner' => [
            'explicit' => [
                'organization.update',
                'organization.manage_billing',
                'navigation.view',
                'navigation.manage',
                'navigation.create',
                'navigation.update',
                'navigation.delete',
                'navigation.reorder',
                'branch.manage',
                'role.view',
                'role.create',
                'role.update',
                'role.delete',
                'role.assign',
                'staff.view',
                'staff.create',
                'staff.update',
                'staff.delete',
                'staff.reset_password',
                'report.view_financial',
                'transaction.void',
                'member.export',
                'member.import',
            ],
            'inherit' => ['Admin'],
        ],
        'Admin' => [
            'explicit' => [
                'branch.view',
                'branch.create',
                'branch.update',
                'branch.delete',
                'transaction.refund',
                'report.view_occupancy',
                'report.export',
            ],
            'inherit' => ['Staff'],
        ],
        'Staff' => [
            'explicit' => [
                'member.view.any',
                'member.create',
                'member.update.basic',
                'member.delete',
                'transaction.create',
                'transaction.view',
                'dashboard.view_stats',
                'organization.view',
            ],
            'inherit' => [],
        ],
        'Member' => [
            'explicit' => [
                'member.view.own',
            ],
            'inherit' => [],
        ],
    ];

    public static function roles(): array
    {
        return self::ROLES;
    }

    public static function permissions(): array
    {
        return collect(self::ROLES)
            ->flatMap(fn (array $role) => $role['explicit'] ?? [])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
