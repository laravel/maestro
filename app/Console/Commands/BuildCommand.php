<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build {--kit= : The starter kit to build (Livewire, React, or Vue)} {--workos : Build the WorkOS variant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds one of the Starter Kits';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $availableKits = config('maestro.starter_kits');
        $kit = $this->option('kit');

        if ($kit) {
            $kit = $this->validateKit($kit, $availableKits);

            if (! $kit) {
                return self::FAILURE;
            }
        } else {
            $kit = select(
                label: 'Which starter kit would you like to build?',
                options: $availableKits,
            );
        }

        $workos = $this->option('workos');

        if (! $workos) {
            $workos = confirm(
                label: 'Would you like to build the WorkOS variant?',
                default: false,
            );
        }

        $variantLabel = $workos ? "{$kit} (WorkOS)" : $kit;
        info("Building {$variantLabel} starter kit...");

        if ($kit === 'Livewire') {
            return $this->buildLivewireKit($workos);
        }

        return $this->buildInertiaKit($kit, $workos);
    }

    /**
     * Validate the kit option against available kits.
     */
    protected function validateKit(string $kit, array $availableKits): ?string
    {
        foreach ($availableKits as $availableKit) {
            if (strtolower($availableKit) === strtolower($kit)) {
                return $availableKit;
            }
        }

        error("Invalid kit '{$kit}'. Available kits are: ".implode(', ', $availableKits));

        return null;
    }

    /**
     * Build the Livewire starter kit.
     */
    protected function buildLivewireKit(bool $workos = false): int
    {
        info('Livewire kit build is not implemented yet.');

        return self::SUCCESS;
    }

    /**
     * Build an Inertia starter kit (React or Vue).
     */
    protected function buildInertiaKit(string $kit, bool $workos = false): int
    {
        $buildPath = base_path('build');
        $basePath = base_path('kits/Inertia/Base');
        $kitPath = base_path("kits/Inertia/{$kit}");

        if (File::exists($buildPath)) {
            File::deleteDirectory($buildPath);
        }
        File::makeDirectory($buildPath, 0755, true);

        info('Copying Base kit files...');
        File::copyDirectory($basePath, $buildPath);

        info("Copying {$kit} kit files...");
        File::copyDirectory($kitPath, $buildPath);

        if ($workos) {
            $this->applyWorkosVariant($buildPath, $kit);
        }

        info('Replacing component placeholders...');
        $this->replacePlaceholders($buildPath, strtolower($kit));

        info('Replacing variant placeholders...');
        $this->replaceVariantPlaceholder($buildPath, strtolower($kit));

        $this->writeStarterKitFile($buildPath, $kit, $workos);

        $this->deleteDatabaseFile($buildPath);

        $variantLabel = $workos ? "{$kit} (WorkOS)" : $kit;
        info("{$variantLabel} starter kit built successfully in the 'build' folder.");
        info("Run './run-kit.sh' to start the development server.");

        return self::SUCCESS;
    }

    /**
     * Replace all placeholders in the build folder.
     */
    protected function replacePlaceholders(string $buildPath, string $kit): void
    {
        $uiComponents = config('maestro.ui_components');

        $searchPaths = [
            $buildPath.'/app/Http/Controllers',
            $buildPath.'/app/Providers',
            $buildPath.'/routes',
            $buildPath.'/tests',
        ];

        foreach ($searchPaths as $searchPath) {
            if (! File::exists($searchPath)) {
                continue;
            }

            $files = File::allFiles($searchPath);

            foreach ($files as $file) {
                $content = $file->getContents();
                $modified = false;

                foreach ($uiComponents as $key => $values) {
                    if (! isset($values[$kit])) {
                        continue;
                    }

                    $placeholder = "{{{$key}}}";
                    $replacement = $values[$kit];

                    if (str_contains($content, $placeholder)) {
                        $content = str_replace($placeholder, $replacement, $content);
                        $modified = true;
                    }
                }

                if ($modified) {
                    File::put($file->getPathname(), $content);
                }
            }
        }
    }

    /**
     * Replace the {{variant}} placeholder with the kit name.
     */
    protected function replaceVariantPlaceholder(string $buildPath, string $kit): void
    {
        $composerPath = $buildPath.'/composer.json';

        if (! File::exists($composerPath)) {
            return;
        }

        $content = File::get($composerPath);

        if (str_contains($content, '{{variant}}')) {
            $content = str_replace('{{variant}}', $kit, $content);
            File::put($composerPath, $content);
        }
    }

    /**
     * Apply the WorkOS variant modifications.
     */
    protected function applyWorkosVariant(string $buildPath, string $kit): void
    {
        $workosBasePath = base_path('kits/Inertia/WorkOS/Base');
        $workosKitPath = base_path("kits/Inertia/WorkOS/{$kit}");

        info('Copying WorkOS Base files...');
        File::copyDirectory($workosBasePath, $buildPath);

        info("Copying WorkOS {$kit} files...");
        File::copyDirectory($workosKitPath, $buildPath);

        info('Removing ignored files for WorkOS variant...');
        $this->removeIgnoredFiles($buildPath, $kit);

        info('Adding WorkOS service configuration...');
        $this->addWorkosServiceConfig($buildPath);

        info('Adding WorkOS environment variables...');
        $this->addWorkosEnvVariables($buildPath);
    }

    /**
     * Remove files listed in the workos.ignore config.
     */
    protected function removeIgnoredFiles(string $buildPath, string $kit): void
    {
        $ignoredPaths = config('maestro.workos.ignore', []);

        foreach ($ignoredPaths as $path) {
            $targetPath = $this->resolveIgnorePath($buildPath, $path, $kit);

            if ($targetPath && File::exists($targetPath)) {
                if (File::isDirectory($targetPath)) {
                    File::deleteDirectory($targetPath);
                } else {
                    File::delete($targetPath);
                }
            }
        }
    }

    /**
     * Resolve the ignore path, converting Vue paths to React if necessary.
     */
    protected function resolveIgnorePath(string $buildPath, string $path, string $kit): ?string
    {
        $isVueFile = Str::endsWith($path, '.vue');
        $isTsFile = Str::endsWith($path, '.ts') || Str::endsWith($path, '.tsx');

        if ($kit === 'React') {
            if ($isVueFile) {
                $dirname = pathinfo($path, PATHINFO_DIRNAME);
                $filename = pathinfo($path, PATHINFO_FILENAME);
                $kebabFilename = Str::kebab($filename);
                $path = $dirname.'/'.$kebabFilename.'.tsx';
            } elseif (! $isTsFile) {
                return $buildPath.'/'.$path;
            }
        } elseif ($kit === 'Vue') {
            if ($isTsFile) {
                return null;
            }
        }

        return $buildPath.'/'.$path;
    }

    /**
     * Add the WorkOS service configuration to services.php.
     */
    protected function addWorkosServiceConfig(string $buildPath): void
    {
        $servicesPath = $buildPath.'/config/services.php';

        if (! File::exists($servicesPath)) {
            return;
        }

        $content = File::get($servicesPath);

        $workosEntry = <<<'PHP'

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'secret' => env('WORKOS_API_KEY'),
        'redirect_url' => env('WORKOS_REDIRECT_URL'),
    ],

PHP;

        $content = preg_replace('/(\];)\s*$/', $workosEntry.'$1', $content);
        File::put($servicesPath, $content);
    }

    /**
     * Write the starter kit identifier file.
     */
    protected function writeStarterKitFile(string $buildPath, string $kit, bool $workos): void
    {
        $starterKit = strtolower($kit);
        if ($workos) {
            $starterKit .= '-workos';
        }

        Storage::disk('local')->put('starter_kit', $starterKit);
    }

    /**
     * Add WorkOS environment variables to .env.example.
     */
    protected function addWorkosEnvVariables(string $buildPath): void
    {
        $envExamplePath = $buildPath.'/.env.example';

        if (! File::exists($envExamplePath)) {
            return;
        }

        $content = File::get($envExamplePath);

        $workosEnv = <<<'ENV'
WORKOS_CLIENT_ID=
WORKOS_API_KEY=
WORKOS_REDIRECT_URL="${APP_URL}/authenticate"


ENV;

        $content = preg_replace(
            '/AWS_ACCESS_KEY_ID=/',
            $workosEnv.'AWS_ACCESS_KEY_ID=',
            $content
        );

        File::put($envExamplePath, $content);
    }

    /**
     * Delete the database.sqlite file from the build.
     */
    protected function deleteDatabaseFile(string $buildPath): void
    {
        $databasePath = $buildPath.'/database/database.sqlite';

        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }
    }
}
