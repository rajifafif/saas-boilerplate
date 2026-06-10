<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class SaaSSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            NavigationSeeder::class,
            RoleSeeder::class,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $org = Organization::firstOrCreate(
            ['slug' => 'demo-organization'],
            ['name' => 'Demo Organization', 'type' => 'saas']
        );

        $branch = Branch::withoutGlobalScopes()->firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Main Branch'],
            ['code' => 'MAIN', 'is_active' => true]
        );

        $users = [
            'platform_admin' => $this->createUser('platform-admin@demo.com', 'Platform Administrator'),
            'organization_manager' => $this->createUser('manager@demo.com', 'Organization Manager'),
            'member' => $this->createUser('member@demo.com', 'Organization Member'),
        ];

        foreach ($users as $role => $user) {
            $org->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => $role,
                    'is_default' => true,
                    'joined_at' => now(),
                ],
            ]);
        }

        $rolePermissions = [
            'platform_admin' => Permission::pluck('name')->all(),
            'owner' => Permission::pluck('name')->all(),
            'organization_manager' => [
                'dashboard.view_stats',
                'organization.view',
                'organization.update',
                'organization.manage_billing',
                'branch.view',
                'branch.create',
                'branch.update',
                'branch.delete',
                'staff.view',
                'staff.create',
                'staff.update',
                'staff.delete',
                'role.view',
                'role.create',
                'role.update',
                'role.assign',
                'member.view.any',
                'member.create',
                'member.update.basic',
                'member.delete',
                'transaction.view',
                'report.export',
            ],
            'admin' => [
                'dashboard.view_stats',
                'organization.view',
                'branch.view',
                'branch.create',
                'branch.update',
                'staff.view',
                'staff.create',
                'staff.update',
                'role.view',
                'role.assign',
                'member.view.any',
                'member.update.basic',
            ],
            'staff' => [
                'dashboard.view_stats',
                'organization.view',
                'branch.view',
                'staff.view',
                'member.view.any',
            ],
            'member' => [
                'dashboard.view_stats',
                'organization.view',
                'member.view.own',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'organization_id' => $org->id,
            ]);
            $role->syncPermissions($permissions);
        }

        $this->command?->info('Neutral SaaS seed complete: platform-admin@demo.com, manager@demo.com, member@demo.com / password');
    }

    private function createUser(string $email, string $name): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['password' => Hash::make('password')]
        );

        if (!$user->person) {
            $user->person()->create([
                'id' => $user->id,
                'name' => $name,
            ]);
        }

        return $user;
    }
}
