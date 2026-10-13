<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyProvisioningService;
use App\Support\RoleSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
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
            'name' => 'PT Kelola Akses',
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

    public function test_members_are_listed_with_their_role(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $members = $this->actingIn($owner, $company)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');

        $this->assertCount(2, $members);

        $listed = collect($members)->keyBy('email');

        $this->assertSame('owner', $listed[$owner->email]['role']['slug']);
        $this->assertTrue($listed[$owner->email]['is_owner']);
        $this->assertSame('staff', $listed[$staff->email]['role']['slug']);
        $this->assertFalse($listed[$staff->email]['is_owner']);
        $this->assertSame($this->role($company, RoleSlug::Staff->value)->getKey(), $listed[$staff->email]['role']['id']);
    }

    public function test_a_role_without_the_users_permission_cannot_manage_members(): void
    {
        [$company] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $this->actingIn($staff, $company)
            ->getJson('/api/users')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda tidak memiliki akses untuk melakukan aksi ini (users.view).');

        $this->actingIn($staff, $company)
            ->postJson('/api/users', [
                'name' => 'Penyusup',
                'email' => 'penyusup@contoh.co.id',
                'password' => 'rahasia123',
                'role_id' => $this->role($company, RoleSlug::Staff->value)->getKey(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki akses untuk melakukan aksi ini (users.create).');

        $this->assertFalse(User::query()->where('email', 'penyusup@contoh.co.id')->exists());
    }

    public function test_a_new_member_account_is_created_and_enrolled(): void
    {
        [$company, $owner] = $this->company();

        $this->actingIn($owner, $company)
            ->postJson('/api/users', [
                'name' => 'Siti Gudang',
                'email' => 'siti@contoh.co.id',
                'password' => 'rahasia123',
                'role_id' => $this->role($company, RoleSlug::Staff->value)->getKey(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'siti@contoh.co.id')
            ->assertJsonPath('data.is_owner', false)
            ->assertJsonPath('data.role.slug', 'staff');

        $created = User::query()->where('email', 'siti@contoh.co.id')->firstOrFail();

        $this->assertTrue(Hash::check('rahasia123', $created->password));
        $this->assertTrue($company->users()->whereKey($created->getKey())->exists());
    }

    public function test_an_existing_account_is_enrolled_without_a_duplicate_user(): void
    {
        [$company, $owner] = $this->company();
        $existing = User::factory()->create(['email' => 'mitra@contoh.co.id']);
        $total = User::query()->count();

        $this->actingIn($owner, $company)
            ->postJson('/api/users', [
                'email' => 'mitra@contoh.co.id',
                'role_id' => $this->role($company, RoleSlug::Manager->value)->getKey(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.id', $existing->getKey())
            ->assertJsonPath('data.role.slug', 'manager');

        $this->assertSame($total, User::query()->count());
        $this->assertTrue($company->users()->whereKey($existing->getKey())->exists());
    }

    public function test_a_member_cannot_be_added_twice(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $this->actingIn($owner, $company)
            ->postJson('/api/users', [
                'name' => $staff->name,
                'email' => $staff->email,
                'password' => 'rahasia123',
                'role_id' => $this->role($company, RoleSlug::Admin->value)->getKey(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'Pengguna tersebut sudah menjadi anggota perusahaan ini.');
    }

    public function test_the_owner_role_cannot_be_assigned_to_a_member(): void
    {
        [$company, $owner] = $this->company();

        $this->actingIn($owner, $company)
            ->postJson('/api/users', [
                'name' => 'Calon Pemilik',
                'email' => 'calon@contoh.co.id',
                'password' => 'rahasia123',
                'role_id' => $this->role($company, RoleSlug::Owner->value)->getKey(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Hanya pemilik perusahaan yang dapat memegang peran pemilik.');

        $this->assertFalse(User::query()->where('email', 'calon@contoh.co.id')->exists());
    }

    public function test_a_role_can_be_changed(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);
        $manager = $this->role($company, RoleSlug::Manager->value);

        $this->actingIn($owner, $company)
            ->patchJson('/api/users/'.$staff->getKey(), [
                'name' => $staff->name,
                'email' => $staff->email,
                'role_id' => $manager->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('data.role.slug', 'manager');

        $pivot = $company->users()->whereKey($staff->getKey())->first()->pivot;

        $this->assertSame($manager->getKey(), (int) $pivot->role_id);
    }

    public function test_a_password_is_only_changed_when_it_is_filled_in(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $this->actingIn($owner, $company)
            ->patchJson('/api/users/'.$staff->getKey(), [
                'name' => 'Nama Baru',
                'email' => $staff->email,
                'password' => '',
                'role_id' => $this->role($company, RoleSlug::Staff->value)->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Baru');

        $this->assertTrue(Hash::check('password', $staff->fresh()->password));
    }

    public function test_a_user_cannot_change_their_own_role(): void
    {
        [$company] = $this->company();
        $admin = $this->member($company, RoleSlug::Admin->value);
        $manager = $this->role($company, RoleSlug::Manager->value);

        $this->actingIn($admin, $company)
            ->patchJson('/api/users/'.$admin->getKey(), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => $manager->getKey(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak dapat mengubah peran Anda sendiri.');
    }

    public function test_the_owner_role_cannot_be_changed(): void
    {
        [$company, $owner] = $this->company();
        $manager = $this->role($company, RoleSlug::Manager->value);

        $this->actingIn($owner, $company)
            ->patchJson('/api/users/'.$owner->getKey(), [
                'name' => $owner->name,
                'email' => $owner->email,
                'role_id' => $manager->getKey(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Peran pemilik tidak dapat diubah.');
    }

    public function test_a_member_cannot_remove_themselves(): void
    {
        [$company] = $this->company();
        $admin = $this->member($company, RoleSlug::Admin->value);

        $this->actingIn($admin, $company)
            ->deleteJson('/api/users/'.$admin->getKey())
            ->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak dapat mengeluarkan diri sendiri.');

        $this->assertTrue($company->users()->whereKey($admin->getKey())->exists());
    }

    public function test_the_owner_cannot_be_removed(): void
    {
        [$company, $owner] = $this->company();

        $this->actingIn($owner, $company)
            ->deleteJson('/api/users/'.$owner->getKey())
            ->assertStatus(403)
            ->assertJsonPath('message', 'Pemilik perusahaan tidak dapat dikeluarkan.');

        $this->assertTrue($company->users()->whereKey($owner->getKey())->exists());
    }

    public function test_a_member_can_be_removed_without_deleting_the_account(): void
    {
        [$company, $owner] = $this->company();
        $staff = $this->member($company, RoleSlug::Staff->value);

        $this->actingIn($owner, $company)
            ->deleteJson('/api/users/'.$staff->getKey())
            ->assertOk()
            ->assertJsonPath('data.message', 'Pengguna dikeluarkan dari perusahaan.');

        $this->assertFalse($company->users()->whereKey($staff->getKey())->exists());
        $this->assertNotNull(User::query()->find($staff->getKey()));
    }

    public function test_a_user_outside_the_company_cannot_be_managed(): void
    {
        [$company, $owner] = $this->company();
        $stranger = User::factory()->create();

        $this->actingIn($owner, $company)
            ->patchJson('/api/users/'.$stranger->getKey(), [
                'name' => 'Diambil',
                'email' => $stranger->email,
                'role_id' => $this->role($company, RoleSlug::Staff->value)->getKey(),
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Pengguna tidak ditemukan di perusahaan ini.');

        $this->actingIn($owner, $company)
            ->deleteJson('/api/users/'.$stranger->getKey())
            ->assertStatus(404);
    }
}
