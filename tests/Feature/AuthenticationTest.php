<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CompanyProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Credentials matching the user created in setUp().
     *
     * @var array{email: string, password: string}
     */
    private array $credentials;

    /**
     * Create a user that owns nothing yet.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->credentials = [
            'email' => $user->email,
            'password' => 'password',
        ];
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', $this->credentials);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $this->credentials['email']);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->credentials['email'],
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email atau password salah.')
            ->assertJsonPath('errors', null);
    }

    public function test_login_reports_the_failing_field_for_a_malformed_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'superadmin',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data yang diberikan tidak valid.')
            ->assertJsonPath('errors.email.0', 'Kolom alamat email harus berupa alamat email yang valid.')
            ->assertJsonPath('errors.password', null);
    }

    public function test_login_reports_every_missing_field(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'Kolom alamat email wajib diisi.')
            ->assertJsonPath('errors.password.0', 'Kolom kata sandi wajib diisi.');
    }

    public function test_login_rejects_an_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_a_bearer_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_the_user_and_requested_company(): void
    {
        $user = User::query()->firstOrFail();
        $company = app(CompanyProvisioningService::class)->createFor($user, ['name' => 'PT Maju Jaya']);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->withHeader('X-Company-Id', (string) $company->getKey())
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.company.id', $company->getKey())
            ->assertJsonPath('data.company.role.slug', 'owner')
            ->assertJsonPath('data.permissions.0', 'master.view');
    }

    public function test_me_works_without_a_company_header(): void
    {
        $user = User::query()->firstOrFail();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.company', null)
            ->assertJsonPath('data.permissions', []);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::query()->firstOrFail();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_a_guest_cannot_list_companies(): void
    {
        $this->getJson('/api/companies')
            ->assertStatus(401);
    }
}
