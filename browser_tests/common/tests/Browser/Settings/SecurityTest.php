<?php

use App\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Hash;
use Pest\Browser\Api\AwaitableWebpage;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

beforeEach(function () {
    withoutMiddleware(RequirePassword::class);
});

test('password update section is displayed on security page', function () {
    actingAs(User::factory()->create());

    visit(route('security.edit'))
        ->assertSee('Update password')
        ->assertSee('Ensure your account is using a long, random password to stay secure')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('password can be updated', function () {
    actingAs($user = User::factory()->create());

    visit(route('security.edit'))
        ->fill('current_password', 'password')
        ->fill('password', 'new-password')
        ->fill('password_confirmation', 'new-password')
        ->press('@update-password-button')
        ->assertSee('Password updated.')
        ->assertUrlIs(route('security.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    actingAs($user = User::factory()->create());

    visit(route('security.edit'))
        ->fill('current_password', 'wrong-password')
        ->fill('password', 'new-password')
        ->fill('password_confirmation', 'new-password')
        ->press('@update-password-button')
        ->assertSee('The password is incorrect.')
        ->assertUrlIs(route('security.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Hash::check('wrong-password', $user->refresh()->password))->toBeFalse();
});

test('two-factor authentication section is displayed on security page', function () {
    actingAs(User::factory()->create());

    visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('Two-factor authentication')
        ->assertSee('Manage your two-factor authentication settings')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication shows disabled state by default', function () {
    actingAs(User::factory()->create());

    visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('Enable 2FA')
        ->assertSee('When you enable two-factor authentication')
        ->assertDontSee('Disable 2FA')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication can be enabled and confirmed', function () {
    $user = User::factory()->create();

    actingAs($user);

    $browser = visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('Enable 2FA')
        ->click('Enable 2FA')
        ->assertSee('Enable two-factor authentication')
        ->assertSee('Continue')
        ->click('Continue')
        ->assertSee('Verify authentication code');

    $user->refresh();
    $secret = decrypt($user->two_factor_secret);
    $code = (new Google2FA)->getCurrentOtp($secret);

    fillOTPCode($browser, $code);

    $browser->click('Confirm')
        ->assertSee('Disable 2FA')
        ->assertDontSee('Enable 2FA')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication shows enabled state', function () {
    actingAs(User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([
            'code-1', 'code-2', 'code-3', 'code-4',
            'code-5', 'code-6', 'code-7', 'code-8',
        ])),
    ]));

    visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('Disable 2FA')
        ->assertDontSee('Enable 2FA')
        ->assertSee('View recovery codes')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication recovery codes can be viewed', function () {
    actingAs(User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([
            'code-1', 'code-2', 'code-3', 'code-4',
            'code-5', 'code-6', 'code-7', 'code-8',
        ])),
    ]));

    visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('View recovery codes')
        ->click('View recovery codes')
        ->assertSee('Hide recovery codes')
        ->assertSee('Regenerate codes')
        ->assertSee('Each recovery code can be used once')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication can be enabled, confirmed, and disabled without stale state', function () {
    $user = User::factory()->create();

    actingAs($user);

    $browser = visit(route('security.edit'))
        ->assertSee('Enable 2FA')
        ->click('Enable 2FA')
        ->assertSee('Enable two-factor authentication')
        ->click('Continue')
        ->assertSee('Verify authentication code');

    $user->refresh();
    $secret = decrypt($user->two_factor_secret);
    $code = (new Google2FA)->getCurrentOtp($secret);

    fillOTPCode($browser, $code);

    $browser->click('Confirm')
        ->assertSee('Disable 2FA')
        ->assertDontSee('Enable 2FA');

    $browser->click('Disable 2FA')
        ->assertSee('Enable 2FA')
        ->assertDontSee('Continue setup')
        ->assertDontSee('Disable 2FA')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('security page displays password section without two-factor when feature is disabled', function () {
    config(['fortify.features' => []]);

    actingAs(User::factory()->create());

    visit(route('security.edit'))
        ->assertPathEndsWith('/settings/security')
        ->assertSee('Update password')
        ->assertSee('Ensure your account is using a long, random password to stay secure')
        ->assertDontSee('Two-factor authentication')
        ->assertDontSee('Enable 2FA')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('password can be updated when two-factor feature is disabled', function () {
    config(['fortify.features' => []]);

    actingAs($user = User::factory()->create());

    visit(route('security.edit'))
        ->fill('current_password', 'password')
        ->fill('password', 'new-password')
        ->fill('password_confirmation', 'new-password')
        ->press('@update-password-button')
        ->assertSee('Password updated.')
        ->assertUrlIs(route('security.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

function fillOTPCode(AwaitableWebpage $browser, string $code): AwaitableWebpage
{
    $isInertia = $browser->script(
        "!!document.getElementById('otp')",
    );

    if ($isInertia) {
        return $browser->typeSlowly('#otp', $code, 50);
    }

    // Livewire/Flux: fill each individual OTP input and trigger change
    // so the hidden input and wire:model binding update correctly.
    $jsCode = json_encode($code);
    $browser->script(<<<JS
        (() => {
            const inputs = document.querySelectorAll('[data-flux-otp-input]');
            const code = {$jsCode};
            inputs.forEach((input, i) => {
                input.value = code[i] || '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            // Trigger change on the <ui-otp> container to update wire:model
            const otp = document.querySelector('ui-otp');
            if (otp) {
                otp.dispatchEvent(new Event('input', { bubbles: true }));
                otp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        })()
    JS);

    return $browser;
}
