<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
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
            app(RoleService::class)->assignRole($branch, $owner, 'Owner');

            return $organization;
        });
    }

    /**
     * Initialize standard roles and permissions for the organization.
     */
    protected function initializeRbac(Organization $organization)
    {
        app(RoleService::class)->createOrganizationRoles($organization);
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
