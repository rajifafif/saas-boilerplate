<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Assign a role to a user within a Branch context.
     * $tenant can be a Branch model.
     */
    public function assignRole($tenant, User $user, string $roleName)
    {
        // Spatie's setPermissionsTeamId global state is one way, but explicit assignment is better.
        // But Spatie's API requires setting the global team id before assigning role if using the trait methods.

        setPermissionsTeamId($tenant->id);

        // Ensure role exists for this guard/team? 
        // Spatie Roles with teams are global unless specialized? 
        // Typically with teams=true, roles are unique per team OR global roles assigned to team pivot.
        // Actually, with teams=true, the 'roles' table gets a team_id (nullable). 
        // If team_id is valid, the role is specific to that team.
        // If team_id is null, it's a global role (like Super Admin).
        // For our SaaS, "Owner" is a global concept but assigned PER team.
        // SO: We likely want Global Defined Roles (team_id=NULL) assigned to Users with a specific Team ID in pivot.

        // Let's check Spatie documentation logic:
        // "When using teams, you can assign the same role to a user for different teams."
        // This means the model_has_roles table has the team_id.
        // The Role itself can be global (team_id=null).

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']); // Global role

        $user->assignRole($role); // Uses the currently set setPermissionsTeamId
    }

    /**
     * Seed default roles for a new organization? 
     * Actually, if roles are global (Owner, Staff, Member), we don't need to create them per Org.
     * We just assign them.
     */
    public function ensureGlobalRolesExist()
    {
        $roles = ['platform_admin', 'owner', 'organization_manager', 'admin', 'staff', 'member'];
        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
