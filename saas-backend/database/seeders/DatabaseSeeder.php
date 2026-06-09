<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with generic SaaS boilerplate demo data.
     */
    public function run(): void
    {
        $this->call([
            SaaSSeeder::class,
        ]);
    }
}
