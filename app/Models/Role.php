<?php

namespace App\Models;

use App\Support\RoleSlug;
use App\Support\Traits\BelongsToCompany;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A permission bundle scoped to a single company.
 */
#[Fillable(['company_id', 'name', 'slug', 'permissions', 'is_system'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Users that hold this role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'role_id', 'user_id')
            ->withPivot(['company_id', 'is_owner'])
            ->withTimestamps();
    }

    /**
     * Whether the role grants the given permission key.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->slug === RoleSlug::Owner->value) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }
}
