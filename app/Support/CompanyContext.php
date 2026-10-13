<?php

namespace App\Support;

use App\Models\Company;
use App\Scopes\CompanyScope;
use Closure;

/**
 * Holds the company that is active for the current request (or console run).
 *
 * Every tenant-scoped model reads its `company_id` filter from here through
 * {@see CompanyScope}. Nothing may query tenant data outside of
 * an active context, which is why the scope denies by default.
 */
class CompanyContext
{
    private ?Company $company = null;

    /**
     * Set the active company. Passing null clears the context.
     */
    public function set(?Company $company): void
    {
        $this->company = $company;
    }

    /**
     * The active company instance, or null when no context is set.
     */
    public function company(): ?Company
    {
        return $this->company;
    }

    /**
     * The active company id, or null when no context is set.
     */
    public function id(): ?int
    {
        return $this->company?->getKey() === null ? null : (int) $this->company->getKey();
    }

    /**
     * Whether a company context is currently active.
     */
    public function has(): bool
    {
        return $this->company !== null;
    }

    /**
     * Clear the active context.
     */
    public function clear(): void
    {
        $this->company = null;
    }

    /**
     * Run a callback inside an explicit company context.
     *
     * Intended for console commands, seeders and queued jobs where no HTTP
     * middleware has established a context.
     *
     * @template TReturn
     *
     * @param  (Closure(Company): TReturn)  $callback
     * @return TReturn
     */
    public function run(Company|int $company, Closure $callback): mixed
    {
        $previous = $this->company;

        $this->set(is_int($company) ? Company::query()->findOrFail($company) : $company);

        try {
            return $callback($this->company);
        } finally {
            $this->set($previous);
        }
    }
}
