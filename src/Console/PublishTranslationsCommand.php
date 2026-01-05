<?php

namespace MetaFramework\Support\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfw-support:publish-translations
                          {--force : Overwrite existing files}
                          {--lang=* : Specific language(s) to publish (e.g., en, fr, bg)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish MetaFramework Support translation files';

    /**
     * Available languages
     *
     * @var array
     */
    protected array $availableLanguages = ['en', 'fr', 'bg'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing MetaFramework Support translations...');

        $languagesToPublish = $this->option('lang') ?: $this->availableLanguages;

        $published = [];
        $skipped = [];
        $errors = [];

        foreach ($languagesToPublish as $lang) {
            if (!in_array($lang, $this->availableLanguages)) {
                $this->warn("Language '{$lang}' is not available. Available: " . implode(', ', $this->availableLanguages));
                continue;
            }

            $result = $this->publishLanguage($lang);

            if ($result === true) {
                $published[] = $lang;
            } elseif ($result === false) {
                $skipped[] = $lang;
            } else {
                $errors[] = $lang;
            }
        }

        $this->newLine();

        if (!empty($published)) {
            $this->info('✓ Published translations for: ' . implode(', ', $published));
        }

        if (!empty($skipped)) {
            $this->comment('⊘ Skipped (already exists): ' . implode(', ', $skipped));
        }

        if (!empty($errors)) {
            $this->error('✗ Failed to publish: ' . implode(', ', $errors));
        }

        $this->newLine();
        $this->info('Translations published to: lang/vendor/mfw-support/');
        $this->newLine();
        $this->comment('Available translation keys:');
        $this->line('  - mfw-support::mfw-support.ajax.request_cannot_be_interpreted');
        $this->line('  - mfw-support::mfw-support.ajax.request_cannot_be_processed');
        $this->line('  - mfw-support::mfw-support.errors.error');

        return self::SUCCESS;
    }

    /**
     * Publish a specific language
     *
     * @param string $lang
     * @return bool|null true=published, false=skipped, null=error
     */
    protected function publishLanguage(string $lang): ?bool
    {
        $sourcePath = __DIR__ . '/../../publishable/lang/' . $lang . '/mfw-support.php';
        $destinationPath = $this->laravel->langPath('vendor/mfw-support/' . $lang . '/mfw-support.php');

        // Create destination directory if it doesn't exist
        $destinationDir = dirname($destinationPath);
        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true);
        }

        // Check if file exists and --force flag is not set
        if (File::exists($destinationPath) && !$this->option('force')) {
            return false; // Skipped
        }

        // Copy the file
        if (File::copy($sourcePath, $destinationPath)) {
            return true; // Published
        }

        return null; // Error
    }
}
