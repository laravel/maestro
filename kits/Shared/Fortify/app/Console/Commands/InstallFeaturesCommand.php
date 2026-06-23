<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;
use Laravel\Chisel\Script;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;

class InstallFeaturesCommand extends Command
{
    private const FEATURES = [
        'email-verification',
        'registration',
        '2fa',
        'passkeys',
        'password-confirmation',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:features
        {--all : Enable all starter kit features}
        {--email-verification : Enable email verification}
        {--registration : Enable registration}
        {--2fa : Enable two-factor authentication}
        {--passkeys : Enable passkeys}
        {--password-confirmation : Enable password confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Choose which starter kit features to keep';

    public function handle(): int
    {
        if ($this->shouldDeferInstallerHooks()) {
            return self::SUCCESS;
        }

        if (! file_exists(base_path('chisel.php'))) {
            return self::SUCCESS;
        }

        /** @var Script $script */
        $script = require base_path('chisel.php');

        $featureAnswers = $this->featureAnswers();

        $answers = $script
            ->collectAnswers()
            ->onQuestion(fn (Question $question) => multiselect(
                label: $question->label,
                options: $question->options,
                default: $question->default ?? [],
                required: $question->required,
                hint: $question->hint,
            ))
            ->interactive($this->input->isInteractive())
            ->withAnswers($featureAnswers);

        $this->installNodeDependencies();

        $script->chisel($answers);

        $this->buildAssets();

        return self::SUCCESS;
    }

    protected function shouldDeferInstallerHooks(): bool
    {
        if ($this->hasFeatureOptions()) {
            return false;
        }

        return filter_var(
            $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS']
                ?? $_SERVER['LARAVEL_INSTALLER_DEFER_HOOKS']
                ?? getenv('LARAVEL_INSTALLER_DEFER_HOOKS'),
            FILTER_VALIDATE_BOOL,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function featureAnswers(): array
    {
        if ($this->option('all')) {
            return ['auth_features' => self::FEATURES];
        }

        $features = array_values(array_filter(
            self::FEATURES,
            fn (string $feature): bool => (bool) $this->option($feature),
        ));

        return $features === [] ? [] : ['auth_features' => $features];
    }

    protected function hasFeatureOptions(): bool
    {
        if ($this->option('all')) {
            return true;
        }

        foreach (self::FEATURES as $feature) {
            if ($this->option($feature)) {
                return true;
            }
        }

        return false;
    }

    protected function installNodeDependencies(): void
    {
        $npm = Chisel::in(base_path())->npm();
        $packageManager = $npm->packageManager();

        spin(
            fn () => $npm->install(),
            "Installing dependencies with {$packageManager->value}...",
        );
    }

    protected function buildAssets(): void
    {
        $npm = Chisel::in(base_path())->npm();

        spin(
            fn () => $npm->run('build'),
            'Building assets...',
        );
    }
}
