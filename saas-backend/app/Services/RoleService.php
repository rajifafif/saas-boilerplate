<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function createOrganizationRoles(Organization $organization): void
    {
        foreach (PermissionCatalog::roles() as $roleName => $config) {
            $role = Role::firstOrCreate([
                'organization_id' => $organization->id,
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if (! empty($config['explicit'])) {
                $role->givePermissionTo($config['explicit']);
            }
        }

        foreach (PermissionCatalog::roles() as $roleName => $config) {
            $role = $organization->roles()->where('name', $roleName)->first();

            foreach ($config['inherit'] ?? [] as $inheritedRoleName) {
                $inheritedRole = $organization->roles()->where('name', $inheritedRoleName)->first();

                if ($role && $inheritedRole) {
                    $role->givePermissionTo($inheritedRole->permissions);
                }
            }
        }

        $this->forgetCachedPermissions();
    }

    public function assignRole(Branch $branch, User $user, string $roleName): void
    {
        $role = Role::query()
            ->where('organization_id', $branch->organization_id)
            ->where('name', $roleName)
            ->first();

        if (! $role) {
            throw new InvalidArgumentException('Role does not belong to the branch organization.');
        }

        $this->assignExistingRole($branch, $user, $role);
    }

    public function assignExistingRole(Branch $branch, User $user, Role $role): void
    {
        if ($role->organization_id !== $branch->organization_id) {
            throw new InvalidArgumentException('Role does not belong to the branch organization.');
        }

        $isMember = $user->organizations()
            ->where('organizations.id', $branch->organization_id)
            ->exists();

        if (! $isMember) {
            throw new InvalidArgumentException('User is not a member of the branch organization.');
        }

        $teamForeignKey = $this->teamForeignKey();

        DB::table(config('permission.table_names.model_has_roles'))->updateOrInsert([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
            $teamForeignKey => $branch->id,
        ], []);

        setPermissionsTeamId($branch->id);
        $this->forgetCachedPermissions();
    }

    public function ensureGlobalRolesExist(): void
    {
        foreach (array_keys(PermissionCatalog::roles()) as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->forgetCachedPermissions();
    }

    public function teamForeignKey(): string
    {
        return config('permission.column_names.team_foreign_key', 'team_id');
    }

    private function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
