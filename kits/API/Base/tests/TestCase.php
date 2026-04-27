<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Gate;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('viewApiDocs', fn (?Authenticatable $user = null): bool => true);
    }

    protected function skipUnlessUserMustVerifyEmail(?string $message = null): void
    {
        if (! is_subclass_of(User::class, MustVerifyEmail::class)) {
            $this->markTestSkipped($message ?? 'User model does not implement MustVerifyEmail.');
        }
    }
}
