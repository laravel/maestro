<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_refresh_their_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->postJson(route('token.refresh'));

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertIsString($response->json('token'));
        $this->assertNotEmpty($response->json('token'));
        $this->assertNotSame($token, $response->json('token'));
    }

    public function test_refresh_rotates_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->assertSame(1, $user->tokens()->count());

        $this->withToken($token)->postJson(route('token.refresh'))->assertOk();

        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_previous_token_is_invalid_after_refresh(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->postJson(route('token.refresh'))->assertOk();

        auth()->forgetGuards();

        $this->withToken($token)->getJson(route('profile.show'))->assertUnauthorized();
    }

    public function test_new_token_authenticates_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $newToken = $this->withToken($token)->postJson(route('token.refresh'))
            ->assertOk()
            ->json('token');

        auth()->forgetGuards();

        $this->withToken($newToken)->getJson(route('profile.show'))->assertOk();
    }

    public function test_refresh_requires_authentication(): void
    {
        $response = $this->postJson(route('token.refresh'));

        $response->assertUnauthorized();
    }

    public function test_replaying_old_token_against_refresh_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->postJson(route('token.refresh'))->assertOk();

        auth()->forgetGuards();

        $this->withToken($token)->postJson(route('token.refresh'))->assertUnauthorized();
    }
}
