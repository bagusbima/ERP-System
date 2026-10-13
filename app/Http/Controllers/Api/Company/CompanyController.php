<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Company directory for the authenticated user.
 */
class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * List every company the user may work with.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $companies = $user->companies()
            ->with('roles')
            ->orderBy('name')
            ->get();

        return $this->success(CompanyResource::collection($companies));
    }

    /**
     * Create a new company and enrol the creator as its owner.
     */
    public function store(CompanyRequest $request, CompanyProvisioningService $provisioning): JsonResponse
    {
        $company = $provisioning->createFor($request->user(), $request->validated());

        return $this->success($this->present($request, $company), status: 201);
    }

    /**
     * Update a company the user owns.
     */
    public function update(CompanyRequest $request, Company $company): JsonResponse
    {
        if (! $request->user()->ownsCompany($company) && ! $request->user()->isSuperadmin()) {
            return $this->failed('Hanya pemilik perusahaan yang dapat mengubah profil perusahaan.', 403);
        }

        $company->update($request->validated());

        return $this->success($this->present($request, $company->fresh()));
    }

    /**
     * Present a company together with the caller's membership pivot.
     */
    private function present(Request $request, Company $company): CompanyResource
    {
        $member = $request->user()->companies()
            ->where('companies.id', $company->getKey())
            ->with('roles')
            ->first();

        return new CompanyResource($member ?? $company->load('roles'));
    }
}
