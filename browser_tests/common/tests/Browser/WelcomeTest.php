<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('welcome screen can be rendered', function () {
    visit('/')
        ->assertSee('Let\'s get started')
        ->assertSee('Log In')
        ->assertSee('Register')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('guests can browse to register page from welcome page', function () {
    visit(route('home'))
        ->click('Register')
        ->assertUrlIs(route('register'))
        ->assertSee('Create an account')
        ->assertSee('Enter your details below to create your account')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('guests can browse to login page from welcome page', function () {
    visit(route('home'))
        ->click('Log in')
        ->assertUrlIs(route('login'))
        ->assertSee('Log in to your account')
        ->assertSee('Enter your email and password below to log in')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('login email receives focus after Vue navigation', function () {
    $dependencies = json_decode(
        file_get_contents(base_path('package.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    )['dependencies'];

    if (! isset($dependencies['vue'])) {
        $this->markTestSkipped('Vue starter kit only.');
    }

    visit(route('home'))
        ->click('Log in')
        ->assertScript('document.activeElement?.getAttribute("name")', 'email')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('authenticated users see dashboard link on welcome page', function () {
    actingAs(User::factory()->create());

    visit(route('home'))
        ->assertSeeLink('Dashboard')
        ->click('Dashboard')
        ->assertUrlIs(route('dashboard'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
