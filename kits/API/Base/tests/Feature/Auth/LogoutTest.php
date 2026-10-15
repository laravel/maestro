<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)->postJson(route('logout'));

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_previous_token_is_invalid_after_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->postJson(route('logout'))->assertNoContent();

        auth()->forgetGuards();

        $this->withToken($token)->getJson(route('profile.show'))->assertUnauthorized();
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson(route('logout'));

        $response->assertUnauthorized();
    }
}
