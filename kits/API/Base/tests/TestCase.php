<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessUserMustVerifyEmail(?string $message = null): void
    {
        if (! is_subclass_of(User::class, MustVerifyEmail::class)) {
            $this->markTestSkipped($message ?? 'User model does not implement MustVerifyEmail.');
        }
    }
}
