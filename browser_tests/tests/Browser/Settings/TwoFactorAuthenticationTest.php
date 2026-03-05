<?php

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;

test('two-factor authentication page can be rendered', function () {
    actingAs(User::factory()->create());

    visitPasswordProtectedPage('two-factor.show')
        ->assertPathEndsWith('/settings/two-factor')
        ->assertSee('Two-factor authentication')
        ->assertSee('Manage your two-factor authentication settings')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication shows disabled state by default', function () {
    actingAs(User::factory()->create());

    visitPasswordProtectedPage('two-factor.show')
        ->assertPathEndsWith('/settings/two-factor')
        ->assertSee('Disabled')
        ->assertSee('Enable 2FA')
        ->assertSee('When you enable two-factor authentication')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('two-factor authentication can be enabled and confirmed', function () {
    $user = User::factory()->create();

    actingAs($user);

    $browser = visitPasswordProtectedPage('two-factor.show')
        ->assertPathEndsWith('/settings/two-factor')
        ->assertSee('Disabled')
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
        ->assertSee('Enabled')
        ->assertSee('Disable 2FA')
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

    visitPasswordProtectedPage('two-factor.show')
        ->assertPathEndsWith('/settings/two-factor')
        ->assertSee('Enabled')
        ->assertSee('Disable 2FA')
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

    visitPasswordProtectedPage('two-factor.show')
        ->assertPathEndsWith('/settings/two-factor')
        ->assertSee('View recovery codes')
        ->click('View recovery codes')
        ->assertSee('Hide recovery codes')
        ->assertSee('Regenerate codes')
        ->assertSee('Each recovery code can be used once')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

function fillOTPCode(AwaitableWebpage $browser, string $code): AwaitableWebpage
{
    $isInertia = $browser->script(
        "!!document.getElementById('otp')"
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
