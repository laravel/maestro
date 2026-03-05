<?php

use App\Models\User;

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
