<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\Webpage;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Visit a route that requires the `password.confirm` middleware.
 *
 * Confirms the password via the confirmation page first, then navigates
 * to the intended destination within the same browser context so the
 * session cookie with `auth.password_confirmed_at` carries over.
 */
function visitPasswordProtectedPage(string $route, string $password = 'password'): AwaitableWebpage|Webpage
{
    $browser = visit(route('password.confirm'))
        ->assertSee('Confirm password')
        ->fill('password', $password);

    // Submit the form via JS and wait for the redirect to complete,
    // avoiding Playwright click-wait-navigation timeout issues.
    $browser->script("document.querySelector('form').submit()");
    $browser->waitForEvent('load');

    return $browser->navigate(route($route));
}
