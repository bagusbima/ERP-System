<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Concerns\ApiResponse;
use App\Http\Concerns\ResolvesActiveCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\CompanyMemberResource;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Support\RoleSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Member directory of the active company.
 *
 * Deciding who belongs to a company and what they may do is a single concern,
 * so every endpoint reuses the `users.*` permission family.
 */
class UserController extends Controller
{
    use ApiResponse, ResolvesActiveCompany;

    /**
     * List the members of the active company together with their role.
     */
    public function index(): JsonResponse
    {
        $company = $this->activeCompany();
        $roles = $company->roles()->get()->keyBy('id');
        $members = $company->users()->orderBy('name')->get();

        foreach ($members as $member) {
            $member->setRelation('role', $roles->get($member->pivot->role_id));
        }

        return $this->success(CompanyMemberResource::collection($members));
    }

    /**
     * Enrol a user in the active company, creating the account when it is new.
     */
    public function store(StoreUserRequest $request, CompanyMembershipService $memberships): JsonResponse
    {
        $company = $this->activeCompany();
        $attributes = $request->validated();

        if ($this->isOwnerRole($company, (int) $attributes['role_id'])) {
            return $this->failed('Hanya pemilik perusahaan yang dapat memegang peran pemilik.', 403);
        }

        $user = $memberships->add($company, $attributes);

        return $this->success($this->present($company, $user), status: 201);
    }

    /**
     * Change the profile, password or role of a company member.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $company = $this->activeCompany();
        $member = $this->findMember($company, $user);

        if ($member === null) {
            return $this->failed('Pengguna tidak ditemukan di perusahaan ini.', 404);
        }

        $attributes = $request->validated();
        $roleId = (int) $attributes['role_id'];
        $changesRole = $roleId !== (int) $member->pivot->role_id;

        if ($changesRole) {
            $denied = $this->rejectRoleChange($company, $member, $request->user(), $roleId);

            if ($denied !== null) {
                return $this->failed($denied, 403);
            }
        }

        $member->update(collect($attributes)
            ->except('role_id')
            ->reject(fn (mixed $value): bool => $value === null)
            ->all());

        if ($changesRole) {
            $company->users()->updateExistingPivot($member->getKey(), ['role_id' => $roleId]);
        }

        return $this->success($this->present($company, $member));
    }

    /**
     * Remove a user from the active company.
     *
     * The account itself is kept so it can be invited into another company
     * later on.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $company = $this->activeCompany();
        $member = $this->findMember($company, $user);

        if ($member === null) {
            return $this->failed('Pengguna tidak ditemukan di perusahaan ini.', 404);
        }

        if ($member->pivot->is_owner) {
            return $this->failed('Pemilik perusahaan tidak dapat dikeluarkan.', 403);
        }

        if ($member->is($request->user())) {
            return $this->failed('Anda tidak dapat mengeluarkan diri sendiri.', 403);
        }

        $company->users()->detach($member->getKey());

        return $this->success(['message' => 'Pengguna dikeluarkan dari perusahaan.']);
    }

    /**
     * Resolve a user and make sure they are actually a member here.
     */
    private function findMember(Company $company, User $user): ?User
    {
        return $company->users()->whereKey($user->getKey())->first();
    }

    /**
     * Whether the role is the reserved owner bundle.
     */
    private function isOwnerRole(Company $company, int $roleId): bool
    {
        return $company->roles()->whereKey($roleId)->value('slug') === RoleSlug::Owner->value;
    }

    /**
     * Explain why a role change is refused, or null when it is allowed.
     */
    private function rejectRoleChange(Company $company, User $member, ?User $actor, int $roleId): ?string
    {
        if ($member->pivot->is_owner) {
            return 'Peran pemilik tidak dapat diubah.';
        }

        if ($member->is($actor)) {
            return 'Anda tidak dapat mengubah peran Anda sendiri.';
        }

        if ($this->isOwnerRole($company, $roleId)) {
            return 'Hanya pemilik perusahaan yang dapat memegang peran pemilik.';
        }

        return null;
    }

    /**
     * Reload the membership so the response carries the fresh pivot and role.
     */
    private function present(Company $company, User $user): CompanyMemberResource
    {
        $member = $this->findMember($company, $user);

        if ($member !== null) {
            $member->setRelation('role', $company->roles()->find($member->pivot->role_id));
        }

        return new CompanyMemberResource($member ?? $user);
    }
}
