<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user_id', 'token']);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_registered_user_receives_verification_email(): void
    {
        $this->skipUnlessUserMustVerifyEmail();

        Notification::fake();

        $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_fails_without_required_fields(): void
    {
        $response = $this->postJson(route('register'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_endpoint_allows_up_to_five_attempts_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('register'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $this->assertNotSame(429, $response->status());
        }
    }

    public function test_register_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('register'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $response = $this->postJson(route('register'), [
            'name' => 'Final User',
            'email' => 'final@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_register_throttle_keys_per_ip(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('register'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $this->postJson(route('register'), [
            'name' => 'Same IP',
            'email' => 'same@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson(route('register'), [
                'name' => 'Different IP',
                'email' => 'different@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertCreated();
    }
}
