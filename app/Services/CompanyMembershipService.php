<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Adds users to a company, creating the underlying account when it is new.
 *
 * Accounts are platform wide: adding somebody who already has an account
 * only creates a membership, so one person can work in several companies
 * with a single set of credentials.
 */
class CompanyMembershipService
{
    /**
     * Enrol a user in the company, creating the account when it does not exist.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function add(Company $company, array $attributes): User
    {
        return DB::transaction(function () use ($company, $attributes): User {
            $user = User::query()
                ->where('email', $attributes['email'])
                ->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                ]);
            }

            $company->users()->attach($user->getKey(), [
                'role_id' => $attributes['role_id'],
                'is_owner' => false,
            ]);

            return $user;
        });
    }
}
