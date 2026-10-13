<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Scopes\CompanyScope;
use App\Support\Permissions;
use App\Support\RoleSlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_superadmin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
        ];
    }

    /**
     * Companies this user is a member of.
     *
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'is_owner'])
            ->withTimestamps();
    }

    /**
     * Whether the user is a platform wide superadmin.
     */
    public function isSuperadmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Whether the user is a member of the given company.
     */
    public function belongsToCompany(Company|int $company): bool
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        return $this->companies()->whereKey($companyId)->exists()
            || $this->isSuperadmin();
    }

    /**
     * The role this user holds inside the given company.
     *
     * The role lookup intentionally bypasses the tenant scope: the caller is
     * resolving membership for a company whose context may not be active yet.
     */
    public function roleIn(Company|int $company): ?Role
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        $roleId = $this->companies()
            ->whereKey($companyId)
            ->first()?->pivot?->role_id;

        if ($roleId === null) {
            return null;
        }

        return Role::withoutGlobalScope(CompanyScope::class)->find($roleId);
    }

    /**
     * Whether the user owns the given company.
     */
    public function ownsCompany(Company|int $company): bool
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        return (bool) $this->companies()
            ->whereKey($companyId)
            ->first()?->pivot?->is_owner;
    }

    /**
     * Every permission key the user holds inside the given company.
     *
     * @return array<int, string>
     */
    public function permissionsFor(Company $company): array
    {
        if ($this->isSuperadmin() || $this->ownsCompany($company)) {
            return Permissions::full();
        }

        $role = $this->roleIn($company);

        if ($role === null) {
            return [];
        }

        if ($role->slug === RoleSlug::Owner->value) {
            return Permissions::full();
        }

        return array_values($role->permissions ?? []);
    }

    /**
     * Whether the user may perform the given permission inside the company.
     */
    public function canPerform(string $permission, Company $company): bool
    {
        return in_array($permission, $this->permissionsFor($company), true);
    }
}
