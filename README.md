<h1 align="center">Maestro</h1>

## Introduction

Maestro is an orchestrator for the [Laravel starter kits](https://laravel.com/starter-kits). You make changes within this repository that will get built out to the individual starter kit repositories.

[Read more about the various starter kit flavors](#starter-kit-flavors).

## Contributing

To contribute bug fixes or features to any of the starter kits, you need to build and run the flavor you want to update.

### Building a Starter Kit

From the `orchestrator` directory, build a kit by running the following command:

```bash
php artisan build
```

This will prompt you to build the starter kit you want. In alternative you can use the `--kit` parameter and the `--workos`, `--components` or `--blank` flags to build directly:

```bash
php artisan build --kit=vue # Builds the Vue (Fortify) starter kit
php artisan build --kit=react --workos # Builds the React (WorkOS) starter kit
php artisan build --kit=livewire --blank # Builds Blank Livewire starter kit
```

### WorkOS

When building a **WorkOS** variant for a starter kit, you can add your **WorkOS** client ID and the API key in the `orchestrator/.env` file, with this, when running the kit, it will copy these values over to the build directory.

### Running the Starter Kit

Once you've built the kit, you can run it with the following command:

```bash
composer kit:run
```

This will start both the standard Laravel development server and a file watcher that automatically copies changes from the `build` folder back to the correct base kit directory.

> [!NOTE]
> While the `build` directory is git ignored, that's where you should make changes to the kits. The file watcher will automatically copy your changes back to the correct location.

### Submitting Changes

After making the changes in `build` and testing that they are working, simply commit your code and create a PR with these changes.

After your PR is merged, **Maestro** will automatically create PRs for the affected starter kits with the updated code.

## Starter Kit Flavors

We have two stacks of starter kits: **Inertia** and **Livewire**. For these stacks, we have several different variations within:

### Livewire

1. **Blank:** a minimal starter kit with no authentication scaffolding.
2. **Fortify:** starter kit using _Laravel Fortify_ for authentication.
3. **Fortify (Multi-file Components):** the same as above, but with the Blade view separated from the component code.
4. **WorkOS:** starter kit using **[WorkOS](https://workos.com)** for authentication.

### Inertia

1. **Blank React:** a minimal _React_ starter kit with no authentication scaffolding.
2. **Fortify React:** _React_ starter kit using _Laravel Fortify_ for authentication.
3. **WorkOS React:** _React_ starter kit using **[WorkOS](https://workos.com)** for authentication.
4. **Blank Vue:** a minimal _Vue_ starter kit with no authentication scaffolding.
5. **Fortify Vue:** _Vue_ starter kit using _Laravel Fortify_ for authentication.
6. **WorkOS Vue:** _Vue_ starter kit using **[WorkOS](https://workos.com)** for authentication.

### Starter Kit Hierarchy

The file hierarchy is as follows:

### Livewire

Blank -> Base -> Auth Layer (Fortify/WorkOS) -> Components (Fortify only variant)

### Inertia

Blank -> Base -> UI Layer (React/Vue) -> Auth Layer (Fortify/WorkOS)

Where `Blank` has the shared files across all variants, and so on.

When applying a change inside the `build` folder, **Maestro** is smart enough to know where that change should be
replicated to, always preferring to apply the change to the customization, so if a file exists both in the
`Blank` and the `Auth Layer`, the change is replicated to the most specific layer (`Auth Layer`) by default.

## Orchestrator

The `orchestrator` directory contains a simple Laravel application that's responsible for building and running the starter kits.

It streamlines the changes by replicating the changes made in the `build` folder - the Starter Kit you're currently running - to the `kits` folder where the files for the different starter kits live.
