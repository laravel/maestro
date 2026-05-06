<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class FortifyMatrixCommand extends Command
{
    protected $signature = 'fortify:matrix
                            {--kit= : The Fortify kit to test (React, Svelte, Vue, or Livewire)}
                            {--teams : Build with the Teams variant}
                            {--components : Build with the Livewire Components variant}
                            {--skip-build : Assume build/ is ready (skip artisan build + deps install)}
                            {--skip-frontend : Skip Bun lint/format/type checks}
                            {--exhaustive : Run every auth_features permutation instead of the curated scenario set}';

    protected $description = 'Run auth_features scenarios for a Fortify kit and lint each result';

    protected const FEATURES = ['email-verification', 'registration', '2fa', 'passkeys', 'password-confirmation'];

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
        $scenarioCount = $this->runScenarios();

        $this->components->info(sprintf('All %d %s passed.', $scenarioCount, $this->option('exhaustive') ? 'permutations' : 'scenarios'));

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

        $this->runStep($label, fn () => $this->runProcess(
            ['php', 'artisan', 'build', '--no-interaction', ...$args],
            base_path(),
        ));

        $this->runStep('Installing Composer dependencies', fn () => $this->runProcess(
            ['composer', 'install', '--no-interaction', '--prefer-dist', '--no-scripts'],
            $this->buildPath,
        ));

        if ($this->hasFrontend()) {
            $this->runStep('Installing Bun dependencies', fn () => $this->runProcess(
                ['bun', 'install'],
                $this->buildPath,
            ));
        }
    }

    protected function snapshotBaseline(): void
    {
        $this->runStep('Snapshotting baseline', fn () => $this->rsync($this->buildPath, $this->baselinePath));
    }

    protected function runScenarios(): int
    {
        $scenarios = $this->option('exhaustive')
            ? $this->exhaustiveScenarios()
            : $this->curatedScenarios();

        collect($scenarios)->each(fn (array $scenario) => $this->runScenario($scenario));

        return count($scenarios);
    }

    /**
     * @param  array{label: string, features: list<string>}  $scenario
     */
    protected function runScenario(array $scenario): void
    {
        $this->runStep($this->scenarioLabel($scenario), function () use ($scenario) {
            $this->rsync($this->baselinePath, $this->buildPath);
            $this->applyChisel($scenario['features']);
            $this->runProcess(['composer', 'lint:check'], $this->buildPath);

            if ($this->shouldCheckFrontend()) {
                $this->runFrontendChecks();
            }
        });
    }

    /**
     * @return list<array{label: string, features: list<string>}>
     */
    protected function curatedScenarios(): array
    {
        $allFeatures = self::FEATURES;

        return collect([
            ['label' => 'all features', 'features' => $allFeatures],
            ['label' => 'no features', 'features' => []],
        ])
            ->merge(collect($allFeatures)->map(fn (string $feature): array => [
                'label' => "{$feature} only",
                'features' => [$feature],
            ]))
            ->merge(collect($allFeatures)->map(fn (string $feature): array => [
                'label' => "all except {$feature}",
                'features' => array_values(array_diff($allFeatures, [$feature])),
            ]))
            ->push([
                'label' => '2fa + passkeys only',
                'features' => ['2fa', 'passkeys'],
            ])
            ->push([
                'label' => 'all except 2fa + passkeys',
                'features' => array_values(array_diff($allFeatures, ['2fa', 'passkeys'])),
            ])
            ->unique(fn (array $scenario): string => implode('|', $scenario['features']))
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, features: list<string>}>
     */
    protected function exhaustiveScenarios(): array
    {
        return collect(range(0, (1 << count(self::FEATURES)) - 1))
            ->map(fn (int $mask): array => [
                'label' => 'permutation '.$mask,
                'features' => $this->featuresFor($mask),
            ])
            ->all();
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
     * @param  array{label: string, features: list<string>}  $scenario
     */
    protected function scenarioLabel(array $scenario): string
    {
        return $scenario['label'].' — '.($scenario['features'] === [] ? 'no features' : implode(', ', $scenario['features']));
    }

    /**
     * @param  list<string>  $features
     */
    protected function applyChisel(array $features): void
    {
        $script = sprintf(
            '$script = require "chisel.php"; $script->run(["auth_features" => %s]);',
            var_export($features, true),
        );

        $this->runProcess(['php', '-r', $script], $this->buildPath);
    }

    protected function runFrontendChecks(): void
    {
        $this->runProcess(['bun', 'install'], $this->buildPath);
        $this->runProcess(['php', 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction'], $this->buildPath);
        $this->runProcess(['bun', 'run', 'lint:check'], $this->buildPath);
        $this->runProcess(['bun', 'run', 'format:check'], $this->buildPath);
        $this->runProcess(['bun', 'run', 'types:check'], $this->buildPath);
    }

    protected function rsync(string $source, string $destination): void
    {
        $this->runProcess([
            'rsync', '-a', '--delete',
            '--exclude', 'vendor',
            '--exclude', 'node_modules',
            rtrim($source, '/').'/',
            rtrim($destination, '/').'/',
        ], base_path());
    }

    protected function runStep(string $label, callable $callback): void
    {
        $this->components->task($label, function () use ($callback) {
            $callback();

            return true;
        });
    }

    /**
     * @param  list<string>  $command
     */
    protected function runProcess(array $command, string $cwd): void
    {
        $pendingProcess = Process::path($cwd)->timeout(0);

        $result = $this->output->isVerbose()
            ? $pendingProcess->run($command, fn ($_, $chunk) => $this->output->write($chunk))
            : $pendingProcess->run($command);

        if ($result->failed()) {
            $this->newLine();
            $this->components->error('Command failed: '.$this->displayCommand($command));
            $this->writeProcessOutput('Output', $result->output());
            $this->writeProcessOutput('Error output', $result->errorOutput());

            throw new RuntimeException('Command failed with exit code '.$result->exitCode().'.');
        }
    }

    /**
     * @param  list<string>  $command
     */
    protected function displayCommand(array $command): string
    {
        return implode(' ', array_map('escapeshellarg', $command));
    }

    protected function writeProcessOutput(string $label, string $output): void
    {
        if (trim($output) === '') {
            return;
        }

        $this->line("<fg=gray>{$label}:</>");
        $this->output->write($output);

        if (! str_ends_with($output, PHP_EOL)) {
            $this->newLine();
        }
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
