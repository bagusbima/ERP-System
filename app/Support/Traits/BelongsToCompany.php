<?php

namespace App\Support\Traits;

use App\Models\Company;
use App\Scopes\CompanyScope;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as belonging to a single company.
 *
 * Applies the {@see CompanyScope} global scope and stamps `company_id` on
 * creation so callers cannot forget tenant isolation.
 */
trait BelongsToCompany
{
    /**
     * Boot the tenant scope and the automatic `company_id` stamping.
     */
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') === null) {
                $model->setAttribute('company_id', app(CompanyContext::class)->id());
            }
        });
    }

    /**
     * The company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
