<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final class StoragePath
{
    public static function resolve(string $path): string
    {
        if ($path === '') {
            return storage_path();
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return storage_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }
}
