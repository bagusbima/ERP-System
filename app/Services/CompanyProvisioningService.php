<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Support\RoleSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates companies together with their system roles and owner membership.
 */
class CompanyProvisioningService
{
    /**
     * Create a company, provision the system roles and enrol the creator.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createFor(User $creator, array $attributes): Company
    {
        return DB::transaction(function () use ($creator, $attributes): Company {
            $company = Company::query()->create([
                ...$attributes,
                'slug' => $attributes['slug'] ?? $this->uniqueSlug($attributes['name']),
            ]);

            foreach (RoleSlug::cases() as $slug) {
                Role::query()->create([
                    'company_id' => $company->getKey(),
                    'name' => $slug->label(),
                    'slug' => $slug->value,
                    'permissions' => $slug->permissions(),
                    'is_system' => true,
                ]);
            }

            $ownerRole = Role::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $company->getKey())
                ->where('slug', RoleSlug::Owner->value)
                ->first();

            $company->users()->attach($creator->getKey(), [
                'role_id' => $ownerRole?->getKey(),
                'is_owner' => true,
            ]);

            return $company;
        });
    }

    /**
     * Build a slug that is not taken yet.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $suffix = 1;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
