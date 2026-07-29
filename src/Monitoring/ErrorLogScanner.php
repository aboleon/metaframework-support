<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final class ErrorLogScanner
{
    public function __construct(
        private readonly LaravelLogParser $parser,
        private readonly LogCursorStore $cursorStore,
    ) {}

    public function scan(): PendingErrorAlerts
    {
        $currentPath = $this->currentLogPath();

        if ($currentPath === null) {
            return new PendingErrorAlerts(null, []);
        }

        $cursor = $this->cursorStore->read();

        if ($cursor === null) {
            return new PendingErrorAlerts(
                new LogCursor($currentPath, $this->fileSize($currentPath)),
                [],
                true,
            );
        }

        return $this->scanFromCursor($cursor, $currentPath);
    }

    public function commit(LogCursor $cursor): void
    {
        $this->cursorStore->write($cursor);
    }

    private function scanFromCursor(LogCursor $cursor, string $currentPath): PendingErrorAlerts
    {
        $entries = [];
        $nextCursor = $cursor;

        foreach ($this->segments($cursor, $currentPath) as [$path, $offset]) {
            [$records, $completeOffset] = $this->records(
                $path,
                $offset,
                $path !== $currentPath,
            );
            $nextCursor = new LogCursor($path, $completeOffset);

            foreach ($records as $record) {
                $nextCursor = new LogCursor($path, $offset + $record->endOffset);

                if ($record->isAlert($this->levels())) {
                    $entries[] = $record->toErrorLogEntry();
                }

                if (count($entries) >= $this->maximumEntries()) {
                    return new PendingErrorAlerts($nextCursor, $entries);
                }
            }
        }

        return new PendingErrorAlerts($nextCursor, $entries);
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function segments(LogCursor $cursor, string $currentPath): array
    {
        if ($cursor->path === $currentPath) {
            return [[
                $currentPath,
                $this->fileSize($currentPath) < $cursor->offset ? 0 : $cursor->offset,
            ]];
        }

        $segments = [];

        if (is_file($cursor->path) && is_readable($cursor->path)) {
            $segments[] = [
                $cursor->path,
                $this->fileSize($cursor->path) < $cursor->offset ? 0 : $cursor->offset,
            ];
        }

        $segments[] = [$currentPath, 0];

        return $segments;
    }

    /**
     * @return array{0: array<int, ParsedLogRecord>, 1: int}
     */
    private function records(string $path, int $offset, bool $includeTrailingContents): array
    {
        $snapshotLength = max(0, $this->fileSize($path) - $offset);

        if ($snapshotLength === 0) {
            return [[], $offset];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false || fseek($handle, $offset) !== 0) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return [[], $offset];
        }

        try {
            $contents = stream_get_contents($handle, $snapshotLength);
        } finally {
            fclose($handle);
        }

        if ($contents === false || $contents === '') {
            return [[], $offset];
        }

        if ($includeTrailingContents) {
            return [
                $this->parser->parse($contents),
                $offset + strlen($contents),
            ];
        }

        $lastNewline = strrpos($contents, "\n");
        if ($lastNewline === false) {
            return [[], $offset];
        }

        $completeContents = substr($contents, 0, $lastNewline + 1);

        return [
            $this->parser->parse($completeContents),
            $offset + strlen($completeContents),
        ];
    }

    private function currentLogPath(): ?string
    {
        $files = [];

        foreach ((array) config('mfw-support.error_alerts.log_patterns', []) as $pattern) {
            foreach (glob(StoragePath::resolve((string) $pattern)) ?: [] as $path) {
                if (is_file($path) && is_readable($path)) {
                    $files[$path] = filemtime($path) ?: 0;
                }
            }
        }

        if ($files === []) {
            return null;
        }

        uksort($files, function (string $left, string $right) use ($files): int {
            return [$files[$right], $right] <=> [$files[$left], $left];
        });

        return array_key_first($files);
    }

    /**
     * @return array<int, string>
     */
    private function levels(): array
    {
        return array_values(array_map(
            static fn (mixed $level): string => strtoupper((string) $level),
            (array) config('mfw-support.error_alerts.levels', []),
        ));
    }

    private function maximumEntries(): int
    {
        return max(1, (int) config('mfw-support.error_alerts.max_entries_per_email', 20));
    }

    private function fileSize(string $path): int
    {
        clearstatcache(true, $path);

        return filesize($path) ?: 0;
    }
}
