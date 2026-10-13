<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roleId = $this->resource->pivot?->role_id;

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'tax_number' => $this->resource->tax_number,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'postal_code' => $this->resource->postal_code,
            'currency' => $this->resource->currency,
            'timezone' => $this->resource->timezone,
            'fiscal_year_start_month' => $this->resource->fiscal_year_start_month,
            'is_active' => $this->resource->is_active,
            'is_owner' => (bool) $this->resource->pivot?->is_owner,
            'role' => $this->resource->relationLoaded('roles')
                ? $this->resolveRole($roleId)
                : null,
        ];
    }

    /**
     * Pick the assigned role out of the eagerly loaded role collection.
     */
    private function resolveRole(?int $roleId): ?array
    {
        if ($roleId === null) {
            return null;
        }

        /** @var Role|null $role */
        $role = $this->resource->roles->firstWhere('id', $roleId);

        if ($role === null) {
            return null;
        }

        return [
            'id' => $role->getKey(),
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => $role->permissions ?? [],
        ];
    }
}
