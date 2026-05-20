<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth',
        ]);
    }

    public function test_login_returns_bearer_token_format(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_user_can_login_with_a_device_name(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone',
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', __('auth.failed'));
    }

    public function test_login_checks_a_password_hash_for_nonexistent_email(): void
    {
        Hash::shouldReceive('check')
            ->once()
            ->withArgs(function (string $password, string $hash): bool {
                return $password === 'password'
                    && password_get_info($hash)['algoName'] !== 'unknown';
            })
            ->andReturnFalse();

        $response = $this->postJson(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', __('auth.failed'));
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson(route('login'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }
}
