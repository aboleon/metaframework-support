<?php

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

        $sourcePath = __DIR__ . '/../../publishable/public/js/mfw-ajax.js';
        $destinationPath = public_path('vendor/mfw-support/js/mfw-ajax.js');

        // Create destination directory if it doesn't exist
        $destinationDir = dirname($destinationPath);
        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true);
            $this->info("Created directory: {$destinationDir}");
        }

        // Check if file exists and --force flag is not set
        if (File::exists($destinationPath) && !$this->option('force')) {
            $this->warn('Asset file already exists. Use --force to overwrite.');

            if (!$this->confirm('Do you want to overwrite the existing file?')) {
                $this->info('Publishing cancelled.');
                return self::SUCCESS;
            }
        }

        // Copy the file
        if (File::copy($sourcePath, $destinationPath)) {
            $this->info('✓ Published: mfw-ajax.js');
            $this->newLine();
            $this->info('Asset published successfully to: public/vendor/mfw-support/js/');
            $this->newLine();
            $this->comment('Include it in your layout:');
            $this->line('<script src="{{ asset(\'vendor/mfw-support/js/mfw-ajax.js\') }}"></script>');

            // Also publish translations if requested
            if ($this->option('with-translations')) {
                $this->newLine();
                $this->call('mfw-support:publish-translations', [
                    '--force' => $this->option('force')
                ]);
            }

            return self::SUCCESS;
        }

        $this->error('Failed to publish assets.');
        return self::FAILURE;
    }
}
