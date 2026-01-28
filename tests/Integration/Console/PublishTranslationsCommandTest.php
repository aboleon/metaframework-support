<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Integration\Console;

use Illuminate\Support\Facades\File;
use MetaFramework\Support\Tests\TestCase;

class PublishTranslationsCommandTest extends TestCase
{
    public function test_publish_translations_command_publishes_all_languages(): void
    {
        $this->artisan('mfw-support:publish-translations')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(lang_path('vendor/mfw-support/en/mfw-support.php')));
        $this->assertTrue(File::exists(lang_path('vendor/mfw-support/fr/mfw-support.php')));
        $this->assertTrue(File::exists(lang_path('vendor/mfw-support/bg/mfw-support.php')));
    }

    public function test_publish_translations_command_respects_lang_filter(): void
    {
        $this->artisan('mfw-support:publish-translations', ['--lang' => ['fr']])
            ->assertExitCode(0);

        $this->assertFalse(File::exists(lang_path('vendor/mfw-support/en/mfw-support.php')));
        $this->assertTrue(File::exists(lang_path('vendor/mfw-support/fr/mfw-support.php')));
        $this->assertFalse(File::exists(lang_path('vendor/mfw-support/bg/mfw-support.php')));
    }

    public function test_publish_translations_command_skips_existing_without_force(): void
    {
        $destination = lang_path('vendor/mfw-support/en/mfw-support.php');

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, '<?php return ["stub" => true];');

        $this->artisan('mfw-support:publish-translations', ['--lang' => ['en']])
            ->assertExitCode(0);

        $this->assertSame('<?php return ["stub" => true];', File::get($destination));
    }
}
