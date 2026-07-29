<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

use RuntimeException;

final class LogCursorStore
{
    public function read(): ?LogCursor
    {
        $path = $this->path();

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (!is_array($payload)
            || !is_string($payload['path'] ?? null)
            || !is_int($payload['offset'] ?? null)
            || $payload['offset'] < 0) {
            return null;
        }

        return new LogCursor($payload['path'], $payload['offset']);
    }

    public function write(LogCursor $cursor): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create error alert cursor directory [{$directory}].");
        }

        $payload = json_encode([
            'path' => $cursor->path,
            'offset' => $cursor->offset,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($payload === false || file_put_contents($path, $payload . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write error alert cursor [{$path}].");
        }
    }

    private function path(): string
    {
        return StoragePath::resolve(
            (string) config(
                'mfw-support.error_alerts.cursor_path',
                'app/mfw-support/error-alert-cursor.json',
            ),
        );
    }
}
