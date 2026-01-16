<h1 align="center">Maestro</h1>
<p align="center"><b>Laravel Starter Kit Orchestrator</b></p>

## Contributing

To contribute to any of the Starter Kits, you need to apply changes to the files in the `kits` directory.

``` bash
Inertia    # Inertia-based starter kits
|__ Base   # Common files shared across all Inertia-based starter kits
|__ React  # Specific files for React starter kit
|__ Vue    # Specific files for Vue starter kit
|__ WorkOS # Specific files for the WorkOS variant for the Inertia-base starter kits
    |__ Base  # Common files shared across all WorkOS Intertia-based starter kits
    |__ React # Specific files for WorkOS React starter kit
    |__ Vue   # Specific files for WorkOS Vue starter kit

Livewire # Livewire-based starter kits
```

## Building a starter kit

In order to validate and test your changes, you'll need to build the starter kit you're changing.
Use this command to build:

```bash
php artisan build
```

This will prompt you to build the starter kit you want. In alternative you can use the `--kit`
parameter and the `--workos` flag to build directly:

```bash
php artisan build --kit=vue # Builds the Vue starter kit
php artisan build --kit=react --workos # Builds the WorkOS variant for the React starter kit
```

## Running the built starter kit

You can use this script, that will run the built starter kit in the `http://localhost:8000`:

```bash
./run-kit.sh
```
