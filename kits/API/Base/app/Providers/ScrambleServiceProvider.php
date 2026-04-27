<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(
            'viewApiDocs',
            fn (?Authenticatable $user = null): bool => app()->environment('local'),
        );

        Scramble::configure()
            ->expose(ui: false, document: '/docs/api.json')
            ->routes(fn (Route $route): bool => is_string($uses = $route->getAction('uses'))
                && str_starts_with($uses, 'App\\Http\\Controllers\\'))
            ->withDocumentTransformers(fn (OpenApi $openApi) => $openApi->secure(
                SecurityScheme::http('bearer'),
            ))
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $hasAuthMiddleware = collect($routeInfo->route->gatherMiddleware())
                    ->contains(fn ($middleware): bool => is_string($middleware)
                        && ($middleware === 'auth' || str_starts_with($middleware, 'auth:')));

                if (! $hasAuthMiddleware) {
                    $operation->security = [];
                }
            });
    }
}
