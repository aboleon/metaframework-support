<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Integration\Console;

use Illuminate\Support\Facades\File;
use MetaFramework\Support\Tests\TestCase;

class PublishAssetsCommandTest extends TestCase
{
    public function test_publish_assets_command_copies_asset(): void
    {
        $destination = public_path('vendor/mfw-support/js/mfw-ajax.js');

        $this->assertFalse(File::exists($destination));

        $this->artisan('mfw-support:publish', ['--force' => true])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($destination));
    }

    public function test_publish_assets_command_respects_existing_file_without_force(): void
    {
        $destination = public_path('vendor/mfw-support/js/mfw-ajax.js');

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, 'original');

        $this->artisan('mfw-support:publish')
            ->expectsConfirmation('Do you want to overwrite the existing file?', 'no')
            ->assertExitCode(0);

        $this->assertSame('original', File::get($destination));
    }
}
