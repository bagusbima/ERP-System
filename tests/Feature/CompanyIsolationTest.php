<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyProvisioningService;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create two companies, each with their own system roles.
     *
     * @return array{first: Company, second: Company}
     */
    private function twoCompanies(): array
    {
        $service = app(CompanyProvisioningService::class);
        $user = User::factory()->create();

        return [
            'first' => $service->createFor($user, ['name' => 'PT Pertama']),
            'second' => $service->createFor($user, ['name' => 'PT Kedua']),
        ];
    }

    public function test_tenant_scope_returns_rows_from_the_active_company_only(): void
    {
        ['first' => $first, 'second' => $second] = $this->twoCompanies();
        $context = app(CompanyContext::class);

        $context->set($first);
        $this->assertSame([$first->getKey()], Role::query()->pluck('company_id')->unique()->values()->all());

        $context->set($second);
        $this->assertSame([$second->getKey()], Role::query()->pluck('company_id')->unique()->values()->all());
    }

    public function test_tenant_scope_denies_every_row_without_an_active_context(): void
    {
        $this->twoCompanies();
        $context = app(CompanyContext::class);

        $context->clear();

        $this->assertSame([], Role::query()->pluck('company_id')->all());
    }

    public function test_company_context_runs_are_restored_afterwards(): void
    {
        ['first' => $first, 'second' => $second] = $this->twoCompanies();
        $context = app(CompanyContext::class);

        $context->set($first);

        $context->run($second, function (Company $company) use ($context, $second): void {
            $this->assertSame($second->getKey(), $context->id());
            $this->assertCount(4, Role::query()->get());
        });

        $this->assertSame($first->getKey(), $context->id());
    }

    public function test_company_id_is_stamped_automatically_on_creation(): void
    {
        ['first' => $first] = $this->twoCompanies();
        $context = app(CompanyContext::class);

        $context->set($first);

        $role = Role::query()->create([
            'name' => 'Kasir',
            'slug' => 'kasir',
            'permissions' => ['sales.view'],
        ]);

        $this->assertSame($first->getKey(), $role->company_id);
    }

    public function test_a_company_scoped_route_requires_the_company_header(): void
    {
        ['first' => $first] = $this->twoCompanies();
        $owner = $first->users()->first();

        $this->withToken($owner->createToken('test')->plainTextToken)
            ->patchJson('/api/companies/'.$first->getKey(), ['name' => 'PT Baru'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Perusahaan belum dipilih. Sertakan header X-Company-Id.');
    }

    public function test_a_non_member_cannot_reach_another_company(): void
    {
        ['first' => $first] = $this->twoCompanies();
        $stranger = User::factory()->create();

        $this->withToken($stranger->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', (string) $first->getKey())
            ->patchJson('/api/companies/'.$first->getKey(), ['name' => 'PT Diambil'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke perusahaan ini.');
    }

    public function test_an_unknown_company_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', '999999')
            ->patchJson('/api/companies/999999', ['name' => 'Tidak Ada'])
            ->assertStatus(404);
    }

    public function test_an_inactive_company_cannot_be_selected(): void
    {
        $user = User::factory()->create();
        $company = app(CompanyProvisioningService::class)->createFor($user, ['name' => 'PT Stop']);
        $company->update(['is_active' => false]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', (string) $company->getKey())
            ->patchJson('/api/companies/'.$company->getKey(), ['name' => 'PT Baru'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Perusahaan tidak aktif.');
    }

    public function test_a_superadmin_can_reach_any_company(): void
    {
        ['first' => $first] = $this->twoCompanies();
        $superadmin = User::factory()->create(['is_superadmin' => true]);

        $this->withToken($superadmin->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', (string) $first->getKey())
            ->patchJson('/api/companies/'.$first->getKey(), ['name' => 'PT Disupervisi'])
            ->assertOk()
            ->assertJsonPath('data.name', 'PT Disupervisi');
    }

    public function test_the_company_context_does_not_leak_between_requests(): void
    {
        ['first' => $first] = $this->twoCompanies();
        $owner = $first->users()->first();

        $this->withToken($owner->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', (string) $first->getKey())
            ->patchJson('/api/companies/'.$first->getKey(), ['name' => 'PT Baru'])
            ->assertOk();

        $this->assertFalse(app(CompanyContext::class)->has());
        $this->assertSame([], Role::query()->pluck('company_id')->all());
    }
}
