<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

class LintFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lint:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build each Inertia variant, run eslint fix, and sync changes back to kits';

    /**
     * The Inertia variants to process.
     *
     * @return array<int, array{display: string, buildArgs: array<string>}>
     */
    protected function variants(): array
    {
        return [
            ['display' => 'React Blank', 'buildArgs' => ['--no-interaction', '--kit=React', '--blank']],
            ['display' => 'React Fortify', 'buildArgs' => ['--no-interaction', '--kit=React']],
            ['display' => 'React WorkOS', 'buildArgs' => ['--no-interaction', '--kit=React', '--workos']],
            ['display' => 'Svelte Blank', 'buildArgs' => ['--no-interaction', '--kit=Svelte', '--blank']],
            ['display' => 'Svelte Fortify', 'buildArgs' => ['--no-interaction', '--kit=Svelte']],
            ['display' => 'Svelte WorkOS', 'buildArgs' => ['--no-interaction', '--kit=Svelte', '--workos']],
            ['display' => 'Vue Blank', 'buildArgs' => ['--no-interaction', '--kit=Vue', '--blank']],
            ['display' => 'Vue Fortify', 'buildArgs' => ['--no-interaction', '--kit=Vue']],
            ['display' => 'Vue WorkOS', 'buildArgs' => ['--no-interaction', '--kit=Vue', '--workos']],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $variants = $this->variants();
        $total = count($variants);
        $buildDir = dirname(base_path()) . '/build';

        foreach ($variants as $index => $variant) {
            $step = $index + 1;
            info("[{$step}/{$total}] {$variant['display']}");

            info('Building variant...');
            $result = $this->runProcess(['php', 'artisan', 'build', ...$variant['buildArgs']], base_path());

            if (! $result->successful()) {
                $this->error("Build failed for {$variant['display']}");

                return self::FAILURE;
            }

            info('Installing npm dependencies...');
            $result = $this->runProcess(['npm', 'install'], $buildDir);

            if (! $result->successful()) {
                $this->error("npm install failed for {$variant['display']}");

                return self::FAILURE;
            }

            info('Running eslint fix...');
            $result = $this->runProcess(['npm', 'run', 'lint'], $buildDir);

            if (! $result->successful()) {
                $this->error("eslint fix failed for {$variant['display']}");

                return self::FAILURE;
            }

            info('Running prettier...');
            $result = $this->runProcess(['npm', 'run', 'format'], $buildDir);

            if (! $result->successful()) {
                $this->error("prettier failed for {$variant['display']}");

                return self::FAILURE;
            }

            info('Syncing changes back to kits...');
            $result = $this->runProcess(['node', 'scripts/watch.js', '--initial-sync-only'], base_path());

            if (! $result->successful()) {
                $this->error("Watcher sync failed for {$variant['display']}");

                return self::FAILURE;
            }

            info("Finished {$variant['display']}");
        }

        info('All Inertia kit variants lint-fixed successfully.');

        return self::SUCCESS;
    }

    /**
     * Run a process with output forwarded to the console.
     */
    protected function runProcess(array $command, string $cwd): \Illuminate\Process\ProcessResult
    {
        $pending = Process::path($cwd)->timeout(300);

        if (\Symfony\Component\Process\Process::isTtySupported()) {
            $pending = $pending->tty();
        }

        return $pending->run(implode(' ', $command), function (string $type, string $output) {
            $this->output->write($output);
        });
    }
}
