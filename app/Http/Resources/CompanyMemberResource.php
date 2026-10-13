<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user together with their membership of the company being addressed.
 *
 * The `role` relation is attached by the controller from the company role
 * collection so a member listing stays at two queries regardless of size.
 *
 * @mixin User
 */
class CompanyMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->resource->relationLoaded('role')
            ? $this->resource->getRelation('role')
            : null;
        $pivot = $this->resource->pivot;

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'is_superadmin' => $this->resource->isSuperadmin(),
            'is_owner' => (bool) $pivot?->is_owner,
            'joined_at' => $pivot?->created_at,
            'role' => $role === null ? null : [
                'id' => $role->getKey(),
                'name' => $role->name,
                'slug' => $role->slug,
            ],
        ];
    }
}
