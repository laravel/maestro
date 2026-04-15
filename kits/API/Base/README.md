# Laravel API Starter Kit

## Introduction

Our Laravel API starter kit provides a headless, API-only starting point for building Laravel applications that power mobile apps, SPAs, or any external client.

It is configured as a **stateless API**: clients authenticate with bearer tokens issued by [Laravel Sanctum](https://laravel.com/docs/sanctum) and no session cookies or CSRF protection are used. If you need Sanctum's stateful, SPA-style cookie authentication instead, you will need to adapt the kit accordingly.

Out of the box it ships with token-based authentication, email verification, password updates, and account deletion endpoints, plus automatically generated API documentation powered by [Scribe](https://scribe.knuckles.wtf).

## Features

- Sanctum-powered personal access tokens with a dedicated refresh endpoint
- Registration, login, and logout endpoints with throttling on sensitive routes
- Profile management: view, update, and delete the authenticated user
- Password update flow gated behind email verification
- Email verification with signed URLs and resend endpoint
- Health check endpoint at the application root
- Auto-generated, interactive API documentation via Scribe

## Packages

- [`laravel/sanctum`](https://laravel.com/docs/sanctum) — API token authentication
- [`knuckleswtf/scribe`](https://scribe.knuckles.wtf) — API documentation generator

## API Documentation

The starter kit uses Scribe to generate live API docs directly from the route definitions, form requests, and API resources.

### Generate the docs

```bash
php artisan scribe:generate
```

This command will:

- Generate an HTML documentation site served at [`/docs`](http://localhost:8000/docs)
- Write an OpenAPI spec to `public/docs/openapi.yaml`
- Write a Postman collection to `public/docs/collection.json`

### Preview the docs

Start the development server and visit the docs in your browser:

```bash
php artisan serve
```

Then open [http://localhost:8000/docs](http://localhost:8000/docs). The built-in "Try It Out" playground lets you call the endpoints straight from the documentation.

To customize the output, edit `config/scribe.php`.

## Official Documentation

Documentation for all Laravel starter kits can be found on the [Laravel website](https://laravel.com/docs/starter-kits).

## Contributing

Thank you for considering contributing to our starter kit! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

All contributions to the Starter Kits from now on should be made through [Maestro](https://github.com/laravel/maestro).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## License

The Laravel API starter kit is open-sourced software licensed under the MIT license.
