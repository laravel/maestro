<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\task;

class FortifyMatrixCommand extends Command
{
    protected $signature = 'fortify:matrix
                            {--kit= : The Fortify kit to test (React, Svelte, Vue, or Livewire)}
                            {--teams : Build with the Teams variant}
                            {--components : Build with the Livewire Components variant}
                            {--skip-build : Assume build/ is ready (skip artisan build + deps install)}
                            {--skip-frontend : Skip Bun lint/format/type checks}';

    protected $description = 'Run every auth_features permutation for a Fortify kit and lint each result';

    protected const FEATURES = ['email-verification', '2fa', 'passkeys', 'password-confirmation'];

    protected string $buildPath;

    protected string $baselinePath;

    public function handle(): int
    {
        $this->validateOptions();

        $this->buildPath = dirname(base_path()).'/build';
        $this->baselinePath = sys_get_temp_dir().'/fortify-matrix-baseline';

        if (! $this->option('skip-build')) {
            $this->buildKit();
        }

        $this->snapshotBaseline();
        $this->runPermutations();

        $this->components->info(sprintf('All %d permutations passed.', 1 << count(self::FEATURES)));

        return self::SUCCESS;
    }

    protected function validateOptions(): void
    {
        if (! $this->option('kit')) {
            $this->fail('--kit is required.');
        }

        if ($this->option('components') && $this->option('kit') !== 'Livewire') {
            $this->fail('--components can only be used with the Livewire kit.');
        }

        if ($this->option('components') && $this->option('teams')) {
            $this->fail('--components cannot be combined with --teams.');
        }
    }

    protected function buildKit(): void
    {
        $kit = $this->option('kit');
        $teams = (bool) $this->option('teams');
        $components = (bool) $this->option('components');

        $args = collect(['--kit='.$kit, '--chisel'])
            ->when($teams, fn ($args) => $args->push('--teams'))
            ->when($components, fn ($args) => $args->push('--components'))
            ->all();

        $label = collect(['Building '.$kit])
            ->when($teams, fn ($parts) => $parts->push('+ Teams'))
            ->when($components, fn ($parts) => $parts->push('+ Components'))
            ->implode(' ');

        task($label, fn ($log) => $this->runProcess(
            ['php', 'artisan', 'build', '--no-interaction', ...$args],
            base_path(),
            $log,
        ));

        task('Installing Composer dependencies', fn ($log) => $this->runProcess(
            ['composer', 'install', '--no-interaction', '--prefer-dist', '--no-scripts'],
            $this->buildPath,
            $log,
        ));

        if ($this->hasFrontend()) {
            task('Installing Bun dependencies', fn ($log) => $this->runProcess(
                ['bun', 'install'],
                $this->buildPath,
                $log,
            ));
        }
    }

    protected function snapshotBaseline(): void
    {
        task('Snapshotting baseline', fn ($log) => $this->rsync($this->buildPath, $this->baselinePath, $log));
    }

    protected function runPermutations(): void
    {
        collect(range(0, (1 << count(self::FEATURES)) - 1))
            ->each(fn ($mask) => $this->runPermutation($mask));
    }

    protected function runPermutation(int $mask): void
    {
        $features = $this->featuresFor($mask);

        task($this->permutationLabel($mask, $features), function ($log) use ($features) {
            $this->rsync($this->baselinePath, $this->buildPath, $log);
            $this->applyChisel($features, $log);
            $this->runProcess(['composer', 'lint:check'], $this->buildPath, $log);

            if ($this->shouldCheckFrontend()) {
                $this->runFrontendChecks($log);
            }
        });
    }

    /**
     * @return list<string>
     */
    protected function featuresFor(int $mask): array
    {
        return collect(self::FEATURES)
            ->filter(fn ($_, $i) => $mask & (1 << $i))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $features
     */
    protected function permutationLabel(int $mask, array $features): string
    {
        return "Permutation {$mask} — ".($features === [] ? 'no features' : implode(', ', $features));
    }

    /**
     * @param  list<string>  $features
     */
    protected function applyChisel(array $features, object $log): void
    {
        $script = sprintf(
            '$script = require "chisel.php"; $script->run(["auth_features" => %s]);',
            var_export($features, true),
        );

        $this->runProcess(['php', '-r', $script], $this->buildPath, $log);
    }

    protected function runFrontendChecks(object $log): void
    {
        $this->runProcess(['bun', 'install'], $this->buildPath, $log);
        $this->runProcess(['php', 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction'], $this->buildPath, $log);
        $this->runProcess(['bun', 'run', 'lint:check'], $this->buildPath, $log);
        $this->runProcess(['bun', 'run', 'format:check'], $this->buildPath, $log);
        $this->runProcess(['bun', 'run', 'types:check'], $this->buildPath, $log);
    }

    protected function rsync(string $source, string $destination, object $log): void
    {
        $this->runProcess([
            'rsync', '-a', '--delete',
            '--exclude', 'vendor',
            '--exclude', 'node_modules',
            rtrim($source, '/').'/',
            rtrim($destination, '/').'/',
        ], base_path(), $log);
    }

    /**
     * @param  list<string>  $command
     */
    protected function runProcess(array $command, string $cwd, object $log): void
    {
        Process::path($cwd)
            ->timeout(0)
            ->run($command, fn ($_, $chunk) => $log->line(rtrim($chunk)))
            ->throw();
    }

    protected function hasFrontend(): bool
    {
        return $this->option('kit') !== 'Livewire';
    }

    protected function shouldCheckFrontend(): bool
    {
        return $this->hasFrontend() && ! $this->option('skip-frontend');
    }
}
