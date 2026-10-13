<?php

namespace App\Http\Requests\User;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changes the profile, password or role of an existing company member.
 *
 * `password` is optional and only written when it is filled in, so leaving
 * the field blank keeps the current credentials untouched.
 */
class UpdateUserRequest extends FormRequest
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
        $member = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($member?->getKey()),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('company_id', $company?->getKey()),
            ],
        ];
    }
}
