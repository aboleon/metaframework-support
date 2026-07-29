<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

use RuntimeException;

final class ErrorAlertLock
{
    /**
     * @return resource|null
     */
    public function acquire(): mixed
    {
        $path = StoragePath::resolve(
            (string) config(
                'mfw-support.error_alerts.cursor_path',
                'app/mfw-support/error-alert-cursor.json',
            ),
        ) . '.lock';
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create error alert lock directory [{$directory}].");
        }

        $handle = fopen($path, 'c');

        if ($handle === false) {
            throw new RuntimeException("Unable to open error alert lock [{$path}].");
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    /**
     * @param  resource|null  $handle
     */
    public function release(mixed $handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
