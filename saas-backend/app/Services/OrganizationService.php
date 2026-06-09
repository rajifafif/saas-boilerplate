<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
    /**
     * Create a new organization and assign owner.
     */
    public function createOrganization(array $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner) {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'type' => $data['type'] ?? 'studio',
                'created_by' => $owner->id,
            ]);

            // Initialize Settings
            $this->initializeSettings($organization);

            // Create Default Branch
            $branch = $organization->branches()->create([
                'name' => 'Main Branch',
                'is_active' => true
            ]);

            // Initialize RBAC (Roles & Permissions)
            $this->initializeRbac($organization);

            // Attach Owner to Organization Users pivot
            $organization->users()->attach($owner->id, [
                'role' => 'owner',
                'is_default' => true,
                'joined_at' => now(),
            ]);

            // Assign Spatie "Owner" role to user, scoped to this org's branch
            $ownerRole = $organization->roles()->where('name', 'Owner')->first();
            if ($ownerRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $ownerRole->id,
                    'model_type' => User::class,
                    'model_id' => $owner->id,
                    'branch_id' => $branch->id, // Spatie team_foreign_key = branch_id
                ]);
            }

            return $organization;
        });
    }

    /**
     * Initialize standard roles and permissions for the organization.
     */
    protected function initializeRbac(Organization $organization)
    {
        // Define Standard Roles and their Permissions
        $roles = [
            'Owner' => [
                // Owner gets everything implicitly or explicitly
                'explicit' => [
                    'organization.update',
                    'organization.manage_billing',
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
                    'member.import'
                ],
                'inherit' => ['Admin']
            ],
            'Admin' => [
                'explicit' => [
                    'branch.view',
                    'branch.create',
                    'branch.update',
                    'branch.delete',
                    'transaction.refund',
                    'report.view_occupancy',
                    'report.export'
                ],
                'inherit' => ['Staff']
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
                    'organization.view'
                ],
                'inherit' => []
            ],
            'Member' => [
                'explicit' => [
                    'member.view.own'
                ],
                'inherit' => []
            ]
        ];

        foreach ($roles as $roleName => $config) {
            // Create Role Scoped to Organization
            $role = \App\Models\Role::create([
                'organization_id' => $organization->id,
                'name' => $roleName,
                'guard_name' => 'web'
            ]);

            // Assign Explicit Permissions
            if (!empty($config['explicit'])) {
                // Sync by name (permissions are global)
                $role->givePermissionTo($config['explicit']);
            }
        }

        // Handle Inheritance (Copy permissions from child to parent)
        // Since Spatie doesn't support Role-extends-Role natively in a simple way without extra recursion queries,
        // we essentially flatten the permissions at creation time for performance.

        $adminRole = $organization->roles()->where('name', 'Admin')->first();
        $staffRole = $organization->roles()->where('name', 'Staff')->first();
        $ownerRole = $organization->roles()->where('name', 'Owner')->first();

        // Admin inherits Staff
        if ($adminRole && $staffRole) {
            $adminRole->givePermissionTo($staffRole->permissions);
        }

        // Owner inherits Admin
        if ($ownerRole && $adminRole) {
            $ownerRole->givePermissionTo($adminRole->permissions);
        }
    }


    protected function initializeSettings(Organization $organization)
    {
        $defaults = [
            'billing.currency' => 'USD',
        ];

        foreach ($defaults as $key => $value) {
            $organization->configurations()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}
