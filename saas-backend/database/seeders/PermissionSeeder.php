<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // These must match the permissions used in OrganizationService::initializeRbac()
        $permissions = [
            // Branch
            'branch.create',
            'branch.delete',
            'branch.manage',
            'branch.update',
            'branch.view',

            // Dashboard
            'dashboard.view_stats',

            // Member
            'member.create',
            'member.delete',
            'member.export',
            'member.import',
            'member.update.basic',
            'member.view.any',
            'member.view.own',

            // Organization
            'organization.manage_billing',
            'organization.update',
            'organization.view',

            // Report
            'report.export',
            'report.view_financial',
            'report.view_occupancy',

            // Role
            'role.assign',
            'role.create',
            'role.delete',
            'role.update',
            'role.view',

            // Staff
            'staff.create',
            'staff.delete',
            'staff.reset_password',
            'staff.update',
            'staff.view',

            // Transaction
            'transaction.create',
            'transaction.refund',
            'transaction.view',
            'transaction.void',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }
}
