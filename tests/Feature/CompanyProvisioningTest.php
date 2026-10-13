<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\CompanyProvisioningService;
use App\Support\RoleSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_roles_are_provisioned_for_a_new_company(): void
    {
        $user = User::factory()->create();

        $company = app(CompanyProvisioningService::class)->createFor($user, [
            'name' => 'PT Contoh Nusantara',
        ]);

        $roles = Role::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->getKey())
            ->pluck('slug')
            ->all();

        sort($roles);

        $this->assertSame(['admin', 'manager', 'owner', 'staff'], $roles);
        $this->assertTrue($company->users()->whereKey($user->getKey())->exists());
        $this->assertTrue((bool) $company->users()->first()->pivot->is_owner);
    }

    public function test_slugs_are_generated_from_the_company_name(): void
    {
        $user = User::factory()->create();
        $service = app(CompanyProvisioningService::class);

        $first = $service->createFor($user, ['name' => 'PT Maju Bersama']);
        $second = $service->createFor($user, ['name' => 'PT Maju Bersama']);

        $this->assertSame('pt-maju-bersama', $first->slug);
        $this->assertSame('pt-maju-bersama-2', $second->slug);
    }

    public function test_an_authenticated_user_can_create_a_company(): void
    {
        $token = User::factory()->create()->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/companies', [
            'name' => 'PT Papan Atas',
            'currency' => 'IDR',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'PT Papan Atas')
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonPath('data.role.slug', 'owner');
    }

    public function test_company_name_is_required(): void
    {
        $token = User::factory()->create()->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/companies', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_only_the_owner_can_update_a_company_profile(): void
    {
        $owner = User::factory()->create();
        $company = app(CompanyProvisioningService::class)->createFor($owner, ['name' => 'PT Dijaga']);

        $ownerToken = $owner->createToken('owner')->plainTextToken;
        $strangerToken = User::factory()->create()->createToken('stranger')->plainTextToken;

        $this->withToken($strangerToken)
            ->withHeader('X-Company-Id', (string) $company->getKey())
            ->patchJson('/api/companies/'.$company->getKey(), ['name' => 'PT Diambil'])
            ->assertStatus(403);

        $this->withToken($ownerToken)
            ->withHeader('X-Company-Id', (string) $company->getKey())
            ->patchJson('/api/companies/'.$company->getKey(), ['name' => 'PT Baru'])
            ->assertOk()
            ->assertJsonPath('data.name', 'PT Baru');

        $this->assertSame('PT Baru', Company::query()->find($company->getKey())->name);
    }

    public function test_a_user_only_sees_the_companies_they_belong_to(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        app(CompanyProvisioningService::class)->createFor($owner, ['name' => 'PT Milik Owner']);

        $ownerToken = $owner->createToken('owner')->plainTextToken;
        $strangerToken = $stranger->createToken('stranger')->plainTextToken;

        $this->withToken($ownerToken)
            ->getJson('/api/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($strangerToken)
            ->getJson('/api/companies')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_system_role_slug_is_reserved(): void
    {
        $user = User::factory()->create();
        $company = app(CompanyProvisioningService::class)->createFor($user, ['name' => 'PT Sistem']);

        $staff = Role::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->getKey())
            ->where('slug', RoleSlug::Staff->value)
            ->first();

        $this->assertFalse($staff->hasPermission('purchasing.approve'));
        $this->assertTrue($staff->hasPermission('master.view'));
    }
}
