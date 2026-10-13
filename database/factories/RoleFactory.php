<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use App\Support\Permissions;
use App\Support\RoleSlug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'company_id' => Company::factory(),
            'name' => ucfirst($slug),
            'slug' => $slug,
            'permissions' => Permissions::full(),
            'is_system' => false,
        ];
    }

    /**
     * Represent one of the system roles provisioned with every company.
     */
    public function system(RoleSlug $slug = RoleSlug::Staff): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $slug->label(),
            'slug' => $slug->value,
            'permissions' => $slug->permissions(),
            'is_system' => true,
        ]);
    }
}
