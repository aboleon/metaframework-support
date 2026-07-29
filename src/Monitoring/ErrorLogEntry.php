<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final readonly class ErrorLogEntry
{
    public function __construct(
        public string $timestamp,
        public string $environment,
        public string $level,
        public string $message,
        public string $raw,
    ) {}
}
