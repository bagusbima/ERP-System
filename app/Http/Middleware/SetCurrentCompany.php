<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active tenant from the `X-Company-Id` header and makes sure the
 * authenticated user is actually a member of it.
 *
 * Must run after `auth:sanctum`; register it inside a route group with the
 * `company` alias rather than appending it to the whole api group.
 */
class SetCurrentCompany
{
    /**
     * Header carrying the active company id.
     */
    public const HEADER = 'X-Company-Id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $companyId = $request->header(self::HEADER);

        if (! is_numeric($companyId)) {
            return $this->error('Perusahaan belum dipilih. Sertakan header X-Company-Id.', 422);
        }

        $company = Company::query()->find((int) $companyId);

        if ($company === null) {
            return $this->error('Perusahaan tidak ditemukan.', 404);
        }

        if (! $user->belongsToCompany($company)) {
            return $this->error('Anda tidak memiliki akses ke perusahaan ini.', 403);
        }

        if (! $company->isActive()) {
            return $this->error('Perusahaan tidak aktif.', 403);
        }

        app(CompanyContext::class)->set($company);

        try {
            return $next($request);
        } finally {
            app(CompanyContext::class)->clear();
        }
    }

    /**
     * Standard error payload for company resolution failures.
     */
    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
