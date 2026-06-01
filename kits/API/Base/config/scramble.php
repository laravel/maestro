<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [
    /*
     * Which routes to document. The kit registers API routes at the root path,
     * so all routes are considered while docs and package endpoints are
     * excluded from the generated OpenAPI document.
     */
    'api_path' => [
        'include' => '*',
        'exclude' => [
            '/',
            'docs',
            'sanctum/csrf-cookie',
            'storage/*',
        ],
    ],

    /*
     * Your API domain. By default, app domain is used.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => '',
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*', 'api.token'],
            'scheme' => SecurityScheme::http('bearer'),
        ],
    ],
];
