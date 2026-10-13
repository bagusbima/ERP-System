<?php

namespace App\Support;

/**
 * Slugs of the system roles that are provisioned for every new company.
 */
enum RoleSlug: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';

    /**
     * Human readable label used in the UI and seeders.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Administrator',
            self::Manager => 'Manajer',
            self::Staff => 'Staf',
        };
    }

    /**
     * The permission keys granted by this system role.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner, self::Admin => Permissions::full(),
            self::Manager => array_values(array_filter(
                Permissions::all(),
                fn (string $key): bool => ! str_starts_with($key, 'users.'),
            )),
            self::Staff => array_values(array_filter(
                Permissions::all(),
                fn (string $key): bool => ! str_ends_with($key, '.approve')
                    && ! str_starts_with($key, 'users.')
                    && ! str_starts_with($key, 'settings.'),
            )),
        };
    }
}
