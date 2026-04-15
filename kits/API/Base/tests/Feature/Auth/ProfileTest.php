<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_info(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'type', 'attributes' => ['name', 'email', 'email_verified_at', 'created_at', 'updated_at']],
            ])
            ->assertJsonPath('data.id', (string) $user->id)
            ->assertJsonPath('data.attributes.email', $user->email);
    }

    public function test_user_can_update_their_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Updated Name');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_user_can_update_their_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.email', 'new@example.com');

        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_email_verification_is_reset_when_email_changes(): void
    {
        $this->skipUnlessUserMustVerifyEmail();

        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->patchJson('/api/user', [
            'email' => 'new@example.com',
        ])->assertOk();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->patchJson('/api/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_fails_with_invalid_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'email' => 'taken@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_fails_with_empty_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/user', [
            'name' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
