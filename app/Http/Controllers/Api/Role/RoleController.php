<?php

namespace App\Http\Controllers\Api\Role;

use App\Http\Concerns\ApiResponse;
use App\Http\Concerns\ResolvesActiveCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

/**
 * Roles of the active company.
 *
 * Roles belong to the user & access module, so they reuse the `users.*`
 * permission family instead of a dedicated set of keys. The four system roles
 * provisioned with every company are read only: adjusting what they grant is
 * done by creating a custom role.
 */
class RoleController extends Controller
{
    use ApiResponse, ResolvesActiveCompany;

    /**
     * List every role of the active company with its member count.
     */
    public function index(): JsonResponse
    {
        $roles = $this->activeCompany()->roles()
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return $this->success(RoleResource::collection($roles));
    }

    /**
     * Create a custom role.
     */
    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->activeCompany()->roles()->create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return $this->success(new RoleResource($role->loadCount('users')), status: 201);
    }

    /**
     * Change the name or the permissions of a custom role.
     */
    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return $this->failed($this->systemRoleMessage('diubah'), 403);
        }

        $role->update($request->validated());

        return $this->success(new RoleResource($role->loadCount('users')));
    }

    /**
     * Delete a custom role that no member is using.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return $this->failed($this->systemRoleMessage('dihapus'), 403);
        }

        $members = $role->users()->count();

        if ($members > 0) {
            return $this->failed('Peran masih digunakan oleh '.$members.' pengguna.', 403);
        }

        $role->delete();

        return $this->success(['message' => 'Peran berhasil dihapus.']);
    }

    /**
     * Shared refusal for the roles every company is provisioned with.
     */
    private function systemRoleMessage(string $action): string
    {
        return 'Peran bawaan sistem tidak dapat '.$action.'. Buat peran baru untuk menyesuaikan izin.';
    }
}
