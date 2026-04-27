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

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertOk();
        $response->assertJson(['message' => 'Email verified successfully.']);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_event_is_dispatched(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->getJson($verificationUrl);

        Event::assertDispatched(Verified::class);
    }

    public function test_user_can_verify_email_using_the_notification_link(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $verificationUrl = null;

        $this->withToken($token)
            ->postJson(route('verification.send'))
            ->assertAccepted()
            ->assertJson(['message' => 'Verification link sent.']);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function (VerifyEmail $notification) use ($user, &$verificationUrl): bool {
                $verificationUrl = $notification->toMail($user)->actionUrl;

                return $verificationUrl !== null;
            },
        );

        $this->assertNotNull($verificationUrl);

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJson(['message' => 'Email verified successfully.']);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('verification.send'));

        $response->assertAccepted();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_fails_with_tampered_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=invalid-signature', $verificationUrl);
        $response = $this->getJson($tamperedUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_fails_with_expired_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_is_not_re_verified(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertOk();
        $response->assertJson(['message' => 'Email already verified.']);
        Event::assertNotDispatched(Verified::class);
    }

    public function test_invalid_hash_against_verified_user_returns_forbidden_not_already_verified(): void
    {
        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertForbidden();
        $this->assertStringNotContainsString('Email already verified.', (string) $response->getContent());
    }

    public function test_invalid_hash_against_unverified_user_returns_forbidden_with_same_shape(): void
    {
        $verifiedUser = User::factory()->create();
        $unverifiedUser = User::factory()->unverified()->create();

        $verifiedResponse = $this->getJson(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $verifiedUser->id, 'hash' => sha1('wrong-email')],
        ));

        $unverifiedResponse = $this->getJson(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $unverifiedUser->id, 'hash' => sha1('wrong-email')],
        ));

        $verifiedResponse->assertForbidden();
        $unverifiedResponse->assertForbidden();
        $this->assertSame($verifiedResponse->getContent(), $unverifiedResponse->getContent());
        $this->assertFalse($unverifiedUser->fresh()->hasVerifiedEmail());
    }

    public function test_verification_returns_forbidden_for_non_existent_user(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 999999, 'hash' => sha1('any@example.com')],
        );

        $response = $this->getJson($verificationUrl);

        $response->assertForbidden();
    }
}
