<?php

namespace App\Console\Commands;

use App\Enums\StarterKit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
    protected $signature = 'build
                            {--kit= : The starter kit to build (Livewire, React, Svelte, or Vue)}
                            {--blank : Build the Blank variant (no authentication)}
                            {--workos : Build the WorkOS variant}
                            {--components : Build the Livewire Components variant}
                            {--teams : Build the Teams variant}';

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
        $availableKits = StarterKit::values();
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
        $components = $this->option('components');
        $blank = $this->option('blank');
        $teams = $this->option('teams');

        // Apply flag priority: --workos > --components > --blank
        if ($workos) {
            $blank = false;
            $components = false;
        } elseif ($components) {
            $blank = false;
        }

        // Components only applies to Livewire
        if ($components && $kit !== 'Livewire') {
            $components = false;
        }

        // Teams only applies to Fortify or WorkOS variants (not Blank or Components)
        if ($teams && ($blank || $components)) {
            $teams = false;
        }

        // Interactive prompts when no flags provided
        if (! $this->option('kit')) {
            // Ask for auth variant (Blank, Fortify, or WorkOS)
            if (! $workos && ! $blank) {
                $authVariant = select(
                    label: 'Which variant would you like to use?',
                    options: [
                        'blank' => 'Blank (no authentication)',
                        'fortify' => 'Fortify (authentication using Fortify)',
                        'workos' => 'WorkOS (authentication using WorkOS)',
                    ],
                    default: 'fortify',
                );

                $blank = $authVariant === 'blank';
                $workos = $authVariant === 'workos';
            }

            // Ask if user wants Teams feature (for Fortify or WorkOS variants)
            if (! $blank && ! $teams) {
                $teams = confirm(
                    label: 'Would you like to enable the Teams feature?',
                    default: false,
                );
            }

            // For Livewire with Fortify (without Teams), ask for components variant
            if ($kit === 'Livewire' && ! $workos && ! $blank && ! $components && ! $teams) {
                $livewireVariant = select(
                    label: 'Which Livewire variant would you like to use?',
                    options: [
                        'single' => 'Single File Components',
                        'multiple' => 'Multiple File Components',
                    ],
                    default: 'single',
                );

                $components = $livewireVariant === 'multiple';
            }
        }

        $variantLabel = $this->getVariantLabel($kit, $workos, $components, $blank, $teams);
        info("Building {$variantLabel} starter kit...");

        return $kit === 'Livewire'
            ? $this->buildLivewireKit($workos, $components, $blank, $teams)
            : $this->buildInertiaKit($kit, $workos, $blank, $teams);
    }

    /**
     * Get the root directory of the maestro project (parent of orchestrator).
     */
    protected function maestroRoot(): string
    {
        return dirname(base_path());
    }

    /**
     * Get the path where the starter kit will be built.
     */
    protected function buildPath(): string
    {
        return $this->maestroRoot().'/build';
    }

    /**
     * Get the path to a kit directory.
     */
    protected function kitPath(string $path = ''): string
    {
        return $this->maestroRoot().'/kits'.($path ? '/'.$path : '');
    }

    /**
     * Get the path to a shared kit directory.
     */
    protected function sharedPath(string $path = ''): string
    {
        return $this->kitPath('Shared'.($path ? '/'.$path : ''));
    }

    /**
     * Get the variant label for display.
     */
    protected function getVariantLabel(
        string $kit,
        bool $workos,
        bool $components,
        bool $blank = false,
        bool $teams = false
    ): string {
        $teamsLabel = $teams ? 'Teams' : '';

        return match (true) {
            $blank => "{$kit} (Blank)",
            $workos => "{$kit} (WorkOS - {$teamsLabel})",
            $components => "{$kit} (Components)",
            default => "{$kit} (Fortify - {$teamsLabel})",
        };
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
     * Prepare the build directory by cleaning and copying base files.
     */
    protected function prepareBuildDirectory(string $basePath): string
    {
        $buildPath = $this->buildPath();

        if (File::exists($buildPath)) {
            File::deleteDirectory($buildPath);
        }
        File::makeDirectory($buildPath, 0755, true);

        info('Copying Shared Blank files...');
        File::copyDirectory($basePath, $buildPath);

        return $buildPath;
    }

    /**
     * Finalize the build by writing metadata and showing success message.
     */
    protected function finalizeBuild(
        string $buildPath,
        string $kit,
        bool $workos,
        bool $components = false,
        bool $blank = false,
        bool $teams = false
    ): int {
        $this->writeStarterKitFile($kit, $workos, $components, $blank, $teams);
        $this->deleteDatabaseFile($buildPath);

        $variantLabel = $this->getVariantLabel($kit, $workos, $components, $blank, $teams);
        info("{$variantLabel} starter kit built successfully in the 'build' folder.");
        info("Run 'composer kit:run' to start the development server.");

        return self::SUCCESS;
    }

    /**
     * Build the Livewire starter kit.
     */
    protected function buildLivewireKit(
        bool $workos = false,
        bool $components = false,
        bool $blank = false,
        bool $teams = false
    ): int {
        $buildPath = $this->prepareBuildDirectory($this->sharedPath('Blank'));

        info('Copying Livewire Blank kit files...');
        File::copyDirectory($this->kitPath('Livewire/Blank'), $buildPath);

        if (! $blank) {
            info('Copying Shared Base files...');
            File::copyDirectory($this->sharedPath('Base'), $buildPath);

            info('Copying Livewire Base kit files...');
            File::copyDirectory($this->kitPath('Livewire/Base'), $buildPath);

            if ($workos) {
                $this->applyWorkosVariant($buildPath, 'Livewire', $teams);
            } else {
                $this->applyFortifyVariant($buildPath, 'Livewire', $teams);

                if ($components) {
                    $this->applyComponentsVariant($buildPath);
                }
            }
        }

        return $this->finalizeBuild($buildPath, 'Livewire', $workos, $components, $blank, $teams);
    }

    /**
     * Build an Inertia starter kit (React or Vue).
     */
    protected function buildInertiaKit(string $kit, bool $workos = false, bool $blank = false, bool $teams = false): int
    {
        $buildPath = $this->prepareBuildDirectory($this->sharedPath('Blank'));

        info('Copying Inertia Blank Base kit files...');
        File::copyDirectory($this->kitPath('Inertia/Blank/Base'), $buildPath);

        info("Copying Inertia Blank {$kit} kit files...");
        File::copyDirectory($this->kitPath("Inertia/Blank/{$kit}"), $buildPath);

        if (! $blank) {
            info('Copying Shared Base files...');
            File::copyDirectory($this->sharedPath('Base'), $buildPath);

            info('Copying Inertia Base kit files...');
            File::copyDirectory($this->kitPath('Inertia/Base'), $buildPath);

            info("Copying Inertia {$kit} kit files...");
            File::copyDirectory($this->kitPath("Inertia/{$kit}"), $buildPath);

            if ($workos) {
                $this->applyWorkosVariant($buildPath, $kit, $teams);
            } else {
                $this->applyFortifyVariant($buildPath, $kit, $teams);
            }
        }

        info('Replacing component placeholders...');
        $this->replacePlaceholders($buildPath, strtolower($kit));

        info('Replacing variant placeholders...');
        $this->replaceVariantPlaceholder($buildPath, strtolower($kit));

        return $this->finalizeBuild($buildPath, $kit, $workos, false, $blank, $teams);
    }

    /**
     * Load the shared kit manifest from the JSON file.
     *
     * @return array{placeholderSearchPaths: string[], componentsRelocations: array<int, array{from: string, to: string, directory?: bool}>, componentsDeleteDirectory: string, kitFolderMap: array<string, string[]>}
     */
    protected function getManifest(): array
    {
        $jsonPath = base_path('scripts/kit-manifest.json');

        return json_decode(File::get($jsonPath), true);
    }

    /**
     * Get the UI components configuration from the JSON file.
     */
    protected function getUiComponents(): array
    {
        $jsonPath = base_path('scripts/ui-components.json');

        return json_decode(File::get($jsonPath), true);
    }

    /**
     * Replace all placeholders in the build folder.
     */
    protected function replacePlaceholders(string $buildPath, string $kit): void
    {
        $uiComponents = $this->getUiComponents();
        $manifest = $this->getManifest();

        $searchPaths = array_map(
            fn (string $relativePath): string => $buildPath.'/'.$relativePath,
            $manifest['placeholderSearchPaths'],
        );

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
    protected function applyWorkosVariant(string $buildPath, string $kit, bool $teams = false): void
    {
        info('Copying Shared WorkOS files...');
        File::copyDirectory($this->sharedPath('WorkOS'), $buildPath);

        if ($kit === 'Livewire') {
            $workosPath = $this->kitPath('Livewire/WorkOS');

            info('Copying Livewire WorkOS files...');
            File::copyDirectory($workosPath, $buildPath);
        } else {
            $workosBasePath = $this->kitPath('Inertia/WorkOS/Base');
            $workosKitPath = $this->kitPath("Inertia/WorkOS/{$kit}");

            info('Copying Inertia WorkOS Base files...');
            File::copyDirectory($workosBasePath, $buildPath);

            info("Copying Inertia WorkOS {$kit} files...");
            File::copyDirectory($workosKitPath, $buildPath);
        }

        if ($teams) {
            $this->applyTeamsVariant($buildPath, $kit, workos: true);
        }
    }

    /**
     * Apply the Fortify auth variant modifications.
     */
    protected function applyFortifyVariant(string $buildPath, string $kit, bool $teams = false): void
    {
        info('Copying Shared Fortify files...');
        File::copyDirectory($this->sharedPath('Fortify'), $buildPath);

        if ($kit === 'Livewire') {
            $fortifyPath = $this->kitPath('Livewire/Fortify');

            info('Copying Livewire Fortify files...');
            File::copyDirectory($fortifyPath, $buildPath);

            if ($teams) {
                $this->applyTeamsVariant($buildPath, $kit, workos: false);
            }

            return;
        }

        $fortifyBasePath = $this->kitPath('Inertia/Fortify/Base');
        $fortifyKitPath = $this->kitPath("Inertia/Fortify/{$kit}");

        info('Copying Inertia Fortify Base files...');
        File::copyDirectory($fortifyBasePath, $buildPath);

        info("Copying Inertia Fortify {$kit} files...");
        File::copyDirectory($fortifyKitPath, $buildPath);

        if ($teams) {
            $this->applyTeamsVariant($buildPath, $kit, workos: false);
        }
    }

    /**
     * Apply the Components variant modifications for Livewire.
     */
    protected function applyComponentsVariant(string $buildPath): void
    {
        $componentsPath = $this->kitPath('Livewire/Components');

        info('Relocating auth views for Components variant...');
        $this->relocateAuthViewsForComponents($buildPath);

        info('Copying Components files...');
        File::copyDirectory($componentsPath, $buildPath);
    }

    /**
     * Apply the Teams variant modifications.
     */
    protected function applyTeamsVariant(string $buildPath, string $kit, bool $workos): void
    {
        info('Copying Shared Teams Base files...');
        File::copyDirectory($this->sharedPath('Teams/Base'), $buildPath);

        $authProvider = $workos ? 'WorkOS' : 'Fortify';

        info("Copying Shared Teams {$authProvider} files...");
        File::copyDirectory($this->sharedPath("Teams/{$authProvider}"), $buildPath);

        if ($kit === 'Livewire') {
            info("Copying Livewire Teams {$authProvider} files...");
            File::copyDirectory($this->kitPath("Livewire/Teams/{$authProvider}"), $buildPath);

            return;
        }

        info('Copying Inertia Teams Base files...');
        File::copyDirectory($this->kitPath('Inertia/Teams/Base'), $buildPath);

        info("Copying Inertia Teams {$kit} files...");
        File::copyDirectory($this->kitPath("Inertia/Teams/{$kit}"), $buildPath);

        info("Copying Inertia Teams {$authProvider} Base files...");
        File::copyDirectory($this->kitPath("Inertia/Teams/{$authProvider}/Base"), $buildPath);

        info("Copying Inertia Teams {$authProvider} {$kit} files...");
        File::copyDirectory($this->kitPath("Inertia/Teams/{$authProvider}/{$kit}"), $buildPath);
    }

    /**
     * Relocate auth views for the Components variant using rules from the shared manifest.
     */
    protected function relocateAuthViewsForComponents(string $buildPath): void
    {
        $manifest = $this->getManifest();

        foreach ($manifest['componentsRelocations'] as $rule) {
            $source = $buildPath.'/'.$rule['from'];
            $dest = $buildPath.'/'.$rule['to'];
            $isDirectory = $rule['directory'] ?? false;

            if (! File::exists(rtrim($source, '/'))) {
                continue;
            }

            File::ensureDirectoryExists($isDirectory ? $dest : dirname($dest));

            if ($isDirectory) {
                File::copyDirectory(rtrim($source, '/'), rtrim($dest, '/'));
            } else {
                File::copy($source, $dest);
            }
        }

        $deleteDir = $buildPath.'/'.$manifest['componentsDeleteDirectory'];

        if (File::exists($deleteDir)) {
            File::deleteDirectory($deleteDir);
        }
    }

    /**
     * Write the starter kit identifier file.
     */
    protected function writeStarterKitFile(
        string $kit,
        bool $workos,
        bool $components = false,
        bool $blank = false,
        bool $teams = false
    ): void {
        $starterKit = strtolower($kit);
        $starterKit .= match (true) {
            $blank => '-blank',
            $workos && $teams => '-workos-teams',
            $workos => '-workos',
            $components => '-components',
            $teams => '-teams',
            default => '',
        };

        Storage::disk('local')->put('starter_kit', $starterKit);
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
