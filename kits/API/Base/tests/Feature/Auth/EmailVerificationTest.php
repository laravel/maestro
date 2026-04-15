<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessUserMustVerifyEmail();
    }

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->withToken($token)->getJson($verificationUrl);

        $response->assertOk();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_event_is_dispatched(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->withToken($token)->getJson($verificationUrl);

        Event::assertDispatched(Verified::class);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/email/verification-notification');

        $response->assertAccepted();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $response = $this->withToken($token)->getJson($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_requires_authentication(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertUnauthorized();
    }

    public function test_already_verified_user_is_not_re_verified(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->withToken($token)->getJson($verificationUrl);

        $response->assertOk();
        Event::assertNotDispatched(Verified::class);
    }
}
