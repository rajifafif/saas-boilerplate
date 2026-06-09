<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new RoleService();
        $service->ensureGlobalRolesExist();
    }
}