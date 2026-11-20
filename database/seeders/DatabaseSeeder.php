<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed non-sensitive RBAC roles/permissions, bootstrap settings, and default CMS pages/menus.
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
            CmsDefaultPagesSeeder::class,
            PakistanDestinationsSeeder::class,
            SampleToursSeeder::class,
            SampleHotelsSeeder::class,
            SampleFleetSeeder::class,
            SampleNomadsSeeder::class,
            SampleSocialAccountsSeeder::class,
        ]);
    }
}
