<?php

namespace App\Http\Requests\Role;

use App\Support\CompanyContext;
use App\Support\Permissions;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Creates or edits a role of the active company.
 *
 * The slug is derived from the name on every write so the administrator
 * never has to think about it; it only has to stay unique inside the company,
 * which also keeps the four system slugs reserved.
 */
class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalise the payload before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $slug = Str::slug((string) $this->input('name'));

        $this->merge([
            'slug' => $slug === '' ? 'peran-'.Str::lower(Str::random(6)) : $slug,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $company = app(CompanyContext::class)->company();
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('roles', 'slug')
                    ->where('company_id', $company?->getKey())
                    ->ignore($role?->getKey()),
            ],
            'permissions' => ['required', 'array'],
            'permissions.*' => [$this->knownPermissionRule()],
        ];
    }

    /**
     * Only keys of the registry may reach the database.
     */
    private function knownPermissionRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                $fail('Izin tidak valid.');

                return;
            }

            if (! Permissions::isValid($value)) {
                $fail('Izin "'.$value.'" tidak dikenal oleh sistem.');
            }
        };
    }
}
