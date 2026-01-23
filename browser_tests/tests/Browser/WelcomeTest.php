<?php

test('welcome screen can be rendered', function () {
    visit('/')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Let\'s get started')
        ->assertSee('Log In')
        ->assertSee('Register');
});

test('guests can browse to register page from welcome page', function () {
    visit(route('home'))
        ->click('Register')
        ->assertUrlIs(route('register'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Create an account')
        ->assertSee('Enter your details below to create your account');
});

test('guests can browse to login page from welcome page', function () {
    visit(route('home'))
        ->click('Log in')
        ->assertUrlIs(route('login'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Log in to your account')
        ->assertSee('Enter your email and password below to log in');
});
