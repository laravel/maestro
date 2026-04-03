<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Process\Factory;
use Laravel\Chisel\NodePackageManager;
use Laravel\Chisel\Question;
use Laravel\Chisel\Script;
use Throwable;

use function Laravel\Prompts\multiselect;

class InstallFeaturesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:features
        {--answers= : JSON string of answers to skip interactive prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Choose which starter kit features to keep';

    public function handle(): int
    {
        try {
            $script = require base_path('chisel.php');

            if (! $script instanceof Script) {
                $this->fail('chisel.php must return a script definition.');
            }

            $providedAnswers = $this->option('answers') === null
                ? []
                : json_decode((string) $this->option('answers'), true, 512, JSON_THROW_ON_ERROR);

            $answers = $this->collectAnswers(
                $script->questions(),
                $providedAnswers,
                $this->input->isInteractive(),
            );

            $script->run($answers);

            $this->rebuildAssets();
            $this->cleanupFiles();
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, Question>  $questions
     * @param  array<string, mixed>  $providedAnswers
     * @return array<string, mixed>
     */
    protected function collectAnswers(array $questions, array $providedAnswers, bool $interactive): array
    {
        $answers = $providedAnswers;

        foreach ($questions as $question) {
            if (array_key_exists($question->name, $answers)) {
                continue;
            }

            if (! $interactive) {
                if ($question->default !== null) {
                    $answers[$question->name] = $question->default;

                    continue;
                }

                if ($question->required) {
                    throw new \RuntimeException("Question [{$question->name}] requires an answer.");
                }

                $answers[$question->name] = [];

                continue;
            }

            $answers[$question->name] = match ($question->type) {
                'multiselect' => multiselect(
                    label: $question->label,
                    options: $question->options,
                    default: $question->default ?? [],
                    required: $question->required,
                    hint: $question->hint,
                ),
                default => throw new \RuntimeException("Unsupported question type [{$question->type}]."),
            };
        }

        return $answers;
    }

    protected function rebuildAssets(): void
    {
        $packageManager = NodePackageManager::detect(base_path());

        $this->info('Installing dependencies with '.$packageManager->value.'...');

        $install = (new Factory)
            ->path(base_path())
            ->forever()
            ->run($packageManager->installProcessCommand(), function (string $type, string $line): void {
                $this->output->write('    '.$line);
            });

        if (! $install->successful()) {
            $this->warn($packageManager->installCommand().' failed. You may need to run "'.$packageManager->installCommand().'" and "'.$packageManager->buildCommand().'" manually.');

            return;
        }

        $this->info('Building assets...');

        $build = (new Factory)
            ->path(base_path())
            ->forever()
            ->run($packageManager->buildProcessCommand(), function (string $type, string $line): void {
                $this->output->write('    '.$line);
            });

        if ($build->successful()) {
            $this->info('Assets built successfully.');

            return;
        }

        $this->warn('Asset build failed. You may need to run "'.$packageManager->buildCommand().'" manually.');
    }

    protected function cleanupFiles(): void
    {
        unlink(base_path('chisel.php'));
        unlink(__FILE__);

        $this->info('Deleted chisel.php');
        $this->info('Deleted install:features command');
    }
}
