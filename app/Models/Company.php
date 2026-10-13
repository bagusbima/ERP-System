<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tenant of the ERP. Company rows are intentionally not tenant scoped:
 * they are only reachable through an explicit membership check.
 */
#[Fillable([
    'name',
    'slug',
    'email',
    'phone',
    'tax_number',
    'address',
    'city',
    'postal_code',
    'currency',
    'timezone',
    'fiscal_year_start_month',
    'logo_path',
    'is_active',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * Users that belong to this company together with their assigned role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_owner'])
            ->withTimestamps();
    }

    /**
     * Roles defined for this company.
     *
     * The tenant scope is intentionally skipped: the relation is always
     * resolved from a company that has already passed the membership check,
     * and the listing endpoints run before any company context exists.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class)->withoutGlobalScope(CompanyScope::class);
    }

    /**
     * Whether the company can still be used for new transactions.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
