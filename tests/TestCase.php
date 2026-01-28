<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests;

use Illuminate\Support\Str;
use MetaFramework\Support\SupportServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $publicPath;

    protected string $langPath;

    protected string $viewCachePath;

    public static function applicationBasePath(): string
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mfw-support-testbench';
        $cachePath = $basePath . DIRECTORY_SEPARATOR . '.phpunit.cache';

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $appPath = $basePath . DIRECTORY_SEPARATOR . 'app';
        $composerPath = $basePath . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_dir($appPath)) {
            mkdir($appPath, 0755, true);
        }

        $composerPayload = '{"autoload":{"psr-4":{"App\\\\":"app/"}}}';

        if (!is_file($composerPath) || file_get_contents($composerPath) !== $composerPayload) {
            file_put_contents($composerPath, $composerPayload);
        }

        return $basePath;
    }

    protected function getPackageProviders($app): array
    {
        return [SupportServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->publicPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mfw-support-public-' . Str::random(8);
        $this->langPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mfw-support-lang-' . Str::random(8);
        $this->viewCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mfw-support-views-' . Str::random(8);

        if (!is_dir($this->publicPath)) {
            mkdir($this->publicPath, 0755, true);
        }

        if (!is_dir($this->langPath)) {
            mkdir($this->langPath, 0755, true);
        }

        if (!is_dir($this->viewCachePath)) {
            mkdir($this->viewCachePath, 0755, true);
        }

        $app->usePublicPath($this->publicPath);
        $app->useLangPath($this->langPath);
        $app['config']->set('view.compiled', $this->viewCachePath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->publicPath ?? null);
        $this->deleteDirectory($this->langPath ?? null);
        $this->deleteDirectory($this->viewCachePath ?? null);

        parent::tearDown();
    }

    private function deleteDirectory(?string $path): void
    {
        if (!$path || !is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
