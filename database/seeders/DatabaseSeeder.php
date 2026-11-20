<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed non-sensitive RBAC roles/permissions and bootstrap settings.
     *
     * Note: The initial Super Admin is NEVER seeded with a hardcoded password.
     * Provision the first Super Admin securely using:
     *   php artisan precious:install-super-admin
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            SiteBootstrapSeeder::class,
        ]);
    }
}
