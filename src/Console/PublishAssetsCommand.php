<?php

declare(strict_types=1);

namespace MetaFramework\Support\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfw-support:publish
                          {--force : Overwrite existing files}
                          {--with-translations : Also publish translation files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish MetaFramework Support assets (JavaScript files and optionally translations)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing MetaFramework Support assets...');

        $assets = [
            'mfw-ajax.js' => [
                'source' => __DIR__ . '/../../publishable/public/js/mfw-ajax.js',
                'destination' => public_path('vendor/mfw-support/js/mfw-ajax.js'),
            ],
            'mfw-action-client.js' => [
                'source' => __DIR__ . '/../../publishable/public/js/mfw-action-client.js',
                'destination' => public_path('vendor/mfw-support/js/mfw-action-client.js'),
            ],
        ];

        // Create destination directory if it doesn't exist
        $destinationDir = dirname($assets['mfw-ajax.js']['destination']);
        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true);
            $this->info("Created directory: {$destinationDir}");
        }

        // Check if file exists and --force flag is not set
        $existingAssets = array_filter($assets, fn (array $asset): bool => File::exists($asset['destination']));
        if ($existingAssets !== [] && !$this->option('force')) {
            $this->warn('One or more asset files already exist. Use --force to overwrite.');

            if (!$this->confirm('Do you want to overwrite the existing file?')) {
                $this->info('Publishing cancelled.');

                return self::SUCCESS;
            }
        }

        // Copy the file
        foreach ($assets as $filename => $asset) {
            if (!File::copy($asset['source'], $asset['destination'])) {
                $this->error("Failed to publish {$filename}.");

                return self::FAILURE;
            }

            $this->info("✓ Published: {$filename}");
        }

        $this->newLine();
        $this->info('Assets published successfully to: public/vendor/mfw-support/js/');
        $this->newLine();
        $this->comment('Include them in your layout:');
        $this->line('<script src="{{ asset(\'vendor/mfw-support/js/mfw-ajax.js\') }}"></script>');
        $this->line('<script src="{{ asset(\'vendor/mfw-support/js/mfw-action-client.js\') }}"></script>');

        // Also publish translations if requested
        if ($this->option('with-translations')) {
            $this->newLine();
            $this->call('mfw-support:publish-translations', [
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }
}
