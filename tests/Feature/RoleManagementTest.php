<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyProvisioningService;
use App\Support\Permissions;
use App\Support\RoleSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Provision a company and hand back its owner.
     *
     * @return array{0: Company, 1: User}
     */
    private function company(): array
    {
        $owner = User::factory()->create();
        $company = app(CompanyProvisioningService::class)->createFor($owner, [
            'name' => 'PT Kelola Peran',
        ]);

        return [$company, $owner];
    }

    /**
     * One of the system roles provisioned with the company.
     */
    private function role(Company $company, string $slug): Role
    {
        return $company->roles()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Enrol a fresh account with the given system role.
     */
    private function member(Company $company, string $slug): User
    {
        $user = User::factory()->create();

        $company->users()->attach($user->getKey(), [
            'role_id' => $this->role($company, $slug)->getKey(),
            'is_owner' => false,
        ]);

        return $user;
    }

    /**
     * Start a request as the given user against the given company.
     *
     * @return $this
     */
    private function actingIn(User $user, Company $company)
    {
        return $this
            ->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('X-Company-Id', (string) $company->getKey());
    }

    public function test_system_roles_are_listed_with_their_member_count(): void
    {
        [$company, $owner] = $this->company();

        $roles = $this->actingIn($owner, $company)
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data')
            ->json('data');

        $this->assertSame(
            ['admin', 'manager', 'owner', 'staff'],
            collect($roles)->pluck('slug')->sort()->values()->all(),
        );

        $ownerRole = collect($roles)->firstWhere('slug', RoleSlug::Owner->value);

        $this->assertTrue($ownerRole['is_system']);
        $this->assertSame(1, $ownerRole['users_count']);
        $this->assertSame(Permissions::full(), $ownerRole['permissions']);
    }

    public function test_a_custom_role_can_be_created(): void
    {
        [$company, $owner] = $this->company();

        $this->actingIn($owner, $company)
            ->postJson('/api/roles', [
                'name' => 'Kasir Gudang',
                'permissions' => ['inventory.view', 'inventory.create', 'sales.view'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Kasir Gudang')
            ->assertJsonPath('data.slug', 'kasir-gudang')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.users_count', 0)
            ->assertJsonPath('data.permissions.0', 'inventory.view')
            ->assertJsonPath('data.permissions.2', 'sales.view');
    }

    public function test_a_role_cannot_take_a_name_that_is_already_used_in_the_company(): void
    {
        [$company, $owner] = $this->company();

        $this->actingIn($owner, $company)
            ->postJson('/api/roles', ['name' => 'Kasir', 'permissions' => ['inventory.view']])
            ->assertStatus(201);

        $second = $this->actingIn($owner, $company)
            ->postJson('/api/roles', ['name' => 'Kasir', 'permissions' => ['inventory.view']])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertArrayHasKey('slug', $second->json('errors'));
        $this->assertSame(5, $company->roles()->count());
    }

    public function test_an_unknown_permission_key_is_rejected(): void
    {
        [$company, $owner] = $this->company();

        $response = $this->actingIn($owner, $company)
            ->postJson('/api/roles', [
                'name' => 'Aneh',
                'permissions' => ['inventory.view', 'inventory.teleport'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $errors = $response->json('errors');

        $this->assertArrayHasKey('permissions.1', $errors);
        $this->assertSame(
            'Izin "inventory.teleport" tidak dikenal oleh sistem.',
            $errors['permissions.1'][0],
        );
        $this->assertFalse($company->roles()->where('slug', 'aneh')->exists());
    }

    public function test_system_roles_are_read_only(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->role($company, RoleSlug::Staff->value);

        $this->actingIn($owner, $company)
            ->patchJson('/api/roles/'.$staff->getKey(), [
                'name' => $staff->name,
                'permissions' => Permissions::full(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Peran bawaan sistem tidak dapat diubah. Buat peran baru untuk menyesuaikan izin.');

        $this->assertSame(
            RoleSlug::Staff->permissions(),
            $company->roles()->whereKey($staff->getKey())->first()->permissions,
        );

        $this->actingIn($owner, $company)
            ->deleteJson('/api/roles/'.$staff->getKey())
            ->assertStatus(403)
            ->assertJsonPath('message', 'Peran bawaan sistem tidak dapat dihapus. Buat peran baru untuk menyesuaikan izin.');

        $this->assertTrue($company->roles()->whereKey($staff->getKey())->exists());
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        [$company, $owner] = $this->company();

        $custom = $this->actingIn($owner, $company)
            ->postJson('/api/roles', ['name' => 'Kasir', 'permissions' => ['inventory.view']])
            ->assertStatus(201)
            ->json('data');

        $user = User::factory()->create();
        $company->users()->attach($user->getKey(), [
            'role_id' => $custom['id'],
            'is_owner' => false,
        ]);

        $this->actingIn($owner, $company)
            ->deleteJson('/api/roles/'.$custom['id'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Peran masih digunakan oleh 1 pengguna.');

        $this->assertTrue($company->roles()->whereKey($custom['id'])->exists());
    }

    public function test_an_unused_custom_role_can_be_deleted(): void
    {
        [$company, $owner] = $this->company();

        $custom = $this->actingIn($owner, $company)
            ->postJson('/api/roles', ['name' => 'Kasir', 'permissions' => ['inventory.view']])
            ->assertStatus(201)
            ->json('data');

        $this->actingIn($owner, $company)
            ->deleteJson('/api/roles/'.$custom['id'])
            ->assertOk()
            ->assertJsonPath('data.message', 'Peran berhasil dihapus.');

        $this->assertSame(4, $company->roles()->count());
    }

    public function test_a_role_from_another_company_cannot_be_touched(): void
    {
        [$first, $firstOwner] = $this->company();
        [$second] = $this->company();

        $foreign = $second->roles()->where('slug', RoleSlug::Staff->value)->firstOrFail();

        $this->actingIn($firstOwner, $first)
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->actingIn($firstOwner, $first)
            ->patchJson('/api/roles/'.$foreign->getKey(), [
                'name' => 'Diambil',
                'permissions' => Permissions::full(),
            ])
            ->assertStatus(404);

        $this->actingIn($firstOwner, $first)
            ->deleteJson('/api/roles/'.$foreign->getKey())
            ->assertStatus(404);
    }

    public function test_the_permission_registry_is_served_with_indonesian_labels(): void
    {
        [$company, $owner] = $this->company();

        $modules = $this->actingIn($owner, $company)
            ->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');

        $this->assertCount(9, $modules);
        $this->assertSame(37, collect($modules)->sum(fn (array $module): int => count($module['actions'])));

        $inventory = collect($modules)->firstWhere('key', 'inventory');

        $this->assertSame('Persediaan', $inventory['label']);
        $this->assertSame('inventory.view', $inventory['actions'][0]['key']);
        $this->assertSame('Melihat', $inventory['actions'][0]['label']);
    }

    public function test_role_management_requires_the_users_permission(): void
    {
        [$company] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $this->actingIn($staff, $company)
            ->getJson('/api/roles')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki akses untuk melakukan aksi ini (users.view).');

        $this->actingIn($staff, $company)
            ->getJson('/api/permissions')
            ->assertStatus(403);

        $this->actingIn($staff, $company)
            ->postJson('/api/roles', ['name' => 'Baru', 'permissions' => ['master.view']])
            ->assertStatus(403);

        $this->actingIn($staff, $company)
            ->patchJson('/api/roles/999999', ['name' => 'Baru', 'permissions' => ['master.view']])
            ->assertStatus(403);

        $this->actingIn($staff, $company)
            ->deleteJson('/api/roles/999999')
            ->assertStatus(403);

        $this->assertSame(4, $company->roles()->count());
    }
}
