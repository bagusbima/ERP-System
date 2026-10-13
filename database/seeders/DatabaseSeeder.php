<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyProvisioningService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Provisioning relies on model events to stamp tenant ids, so this seeder
     * intentionally runs with events enabled.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'superadmin@erp.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_superadmin' => true,
            ],
        );

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@erp.test'],
            [
                'name' => 'Pemilik Demo',
                'password' => 'password',
            ],
        );

        $provisioning = app(CompanyProvisioningService::class);

        $companies = [
            ['name' => 'PT Demo Nusantara', 'city' => 'Jakarta'],
            ['name' => 'CV Contoh Distribusi', 'city' => 'Bandung'],
        ];

        foreach ($companies as $attributes) {
            if (Company::query()->where('name', $attributes['name'])->exists()) {
                continue;
            }

            $provisioning->createFor($owner, $attributes);
        }
    }
}
