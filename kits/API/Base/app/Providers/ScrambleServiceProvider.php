<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(
            'viewApiDocs',
            fn (?Authenticatable $user = null): bool => app()->environment(['local', 'testing']),
        );

        Scramble::configure()
            ->expose(ui: '/docs', document: '/docs/api.json');
    }
}
