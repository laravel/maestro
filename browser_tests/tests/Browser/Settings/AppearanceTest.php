<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('appearance page can be rendered', function () {
    actingAs(User::factory()->create());

    visit(route('appearance.edit'))
        ->assertSee('Appearance')
        ->assertSee('Light')
        ->assertSee('Dark')
        ->assertSee('System')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('user can switch theme to dark mode', function () {
    actingAs(User::factory()->create());

    visit(route('appearance.edit'))
        ->click('Dark')
        ->assertScript('document.documentElement.classList.contains("dark")', true)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('user can switch theme to light mode', function () {
    actingAs(User::factory()->create());

    visit(route('appearance.edit'))
        ->click('Dark')
        ->assertScript('document.documentElement.classList.contains("dark")', true)
        ->click('Light')
        ->assertScript('document.documentElement.classList.contains("dark")', false)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
