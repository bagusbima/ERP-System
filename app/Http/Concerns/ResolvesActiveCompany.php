<?php

namespace App\Http\Concerns;

use App\Http\Middleware\SetCurrentCompany;
use App\Models\Company;
use App\Support\CompanyContext;

/**
 * Reads the tenant established by {@see SetCurrentCompany}.
 *
 * Controllers using this trait are always mounted inside the company group,
 * so a missing context can only mean the route lost its middleware; failing
 * loudly is safer than silently operating without a tenant.
 */
trait ResolvesActiveCompany
{
    /**
     * The company the current request is scoped to.
     */
    protected function activeCompany(): Company
    {
        $company = app(CompanyContext::class)->company();

        if ($company === null) {
            abort(403, 'Perusahaan belum dipilih.');
        }

        return $company;
    }
}
