<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final readonly class ParsedLogRecord
{
    public function __construct(
        public string $timestamp,
        public string $environment,
        public string $level,
        public string $message,
        public string $raw,
        public int $endOffset,
    ) {}

    /**
     * @param  array<int, string>  $levels
     */
    public function isAlert(array $levels): bool
    {
        return in_array($this->level, $levels, true);
    }

    public function toErrorLogEntry(): ErrorLogEntry
    {
        return new ErrorLogEntry(
            timestamp: $this->timestamp,
            environment: $this->environment,
            level: $this->level,
            message: $this->message,
            raw: $this->raw,
        );
    }
}
