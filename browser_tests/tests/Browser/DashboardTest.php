<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('guests are redirected to the login page', function () {
    visit(route('dashboard'))
        ->assertUrlIs(route('login'))
        ->assertSee('Log in to your account')
        ->assertSee('Enter your email and password below to log in')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('authenticated users can visit the dashboard', function () {
    actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->assertUrlIs(route('dashboard'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('authenticated users can toggle the sidebar on mobile', function () {
    actingAs(User::factory()->create());

    visit(route('dashboard'))->on()->mobile()
        ->click('[data-sidebar="trigger"], [data-flux-sidebar-toggle]')
        ->assertVisible('[data-sidebar="sidebar"][data-mobile="true"], [data-flux-sidebar]:not([data-flux-sidebar-collapsed-mobile])')
        ->click('[data-slot="sheet-overlay"], [data-flux-sidebar-backdrop], button[aria-label="Close"]')
        ->assertMissing('[data-sidebar="sidebar"][data-mobile="true"], [data-flux-sidebar]:not([data-flux-sidebar-collapsed-mobile])')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
