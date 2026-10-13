<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adds a member to the active company.
 *
 * Accounts are platform wide, so an email that already belongs to somebody
 * enrols that existing account instead of creating a duplicate. The fields
 * that only matter for a brand new account are made conditional on it.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $company = app(CompanyContext::class)->company();
        $accountExists = $this->accountExists();

        return [
            'name' => $accountExists ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->uniqueMemberRule()],
            'password' => $accountExists ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('company_id', $company?->getKey()),
            ],
        ];
    }

    /**
     * Whether the submitted email already belongs to a platform account.
     */
    private function accountExists(): bool
    {
        $email = (string) $this->input('email');

        return $email !== '' && User::query()->where('email', $email)->exists();
    }

    /**
     * Reject a user that is already a member of this company.
     */
    private function uniqueMemberRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $company = app(CompanyContext::class)->company();

            if ($company === null) {
                return;
            }

            if ($company->users()->where('users.email', (string) $value)->exists()) {
                $fail('Pengguna tersebut sudah menjadi anggota perusahaan ini.');
            }
        };
    }
}
