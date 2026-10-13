<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a single permission key of the active company.
 *
 * Register it with the `permission` alias after `SetCurrentCompany`, for
 * example `->middleware('permission:users.view')`.
 */
class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $company = app(CompanyContext::class)->company();

        if ($company === null || ! $request->user()?->canPerform($permission, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini ('.$permission.').',
            ], 403);
        }

        return $next($request);
    }
}
