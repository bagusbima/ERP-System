<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCurrentCompany;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Token based authentication for the Vue SPA.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Token lifetime for the SPA session, expressed in minutes.
     */
    private const TOKEN_TTL_MINUTES = 60 * 24 * 7;

    /**
     * Issue an API token for valid credentials.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email'))
            ->first();

        if ($user === null || ! Hash::check($request->string('password'), $user->password)) {
            return $this->failed('Email atau password salah.', 401);
        }

        $token = $user->createToken(
            $request->deviceName(),
            ['*'],
            now()->addMinutes(self::TOKEN_TTL_MINUTES),
        )->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            'user' => new UserResource($user->load('companies.roles')),
        ]);
    }

    /**
     * Revoke the token used by the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(['message' => 'Berhasil keluar.']);
    }

    /**
     * The authenticated user together with the active company context.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('companies.roles');

        $company = $this->resolveCompany($user, $request);

        return $this->success([
            'user' => new UserResource($user),
            'company' => $company ? new CompanyResource($company) : null,
            'permissions' => match (true) {
                $company !== null => $user->permissionsFor($company),
                $user->isSuperadmin() => Permissions::full(),
                default => [],
            },
        ]);
    }

    /**
     * Resolve and validate the requested company for the current user.
     *
     * The company is resolved through the membership relation so the pivot
     * (role and ownership flags) is available to the API resource. Superadmins
     * may open a company they are not enrolled in, which has no pivot yet.
     */
    private function resolveCompany(User $user, Request $request): ?Company
    {
        $companyId = $request->header(SetCurrentCompany::HEADER);

        if (! is_numeric($companyId)) {
            return null;
        }

        $member = $user->companies()
            ->where('companies.id', (int) $companyId)
            ->with('roles')
            ->first();

        if ($member !== null) {
            return $member->isActive() ? $member : null;
        }

        if (! $user->isSuperadmin()) {
            return null;
        }

        $company = Company::query()->find((int) $companyId);

        return $company !== null && $company->isActive() ? $company->load('roles') : null;
    }
}
