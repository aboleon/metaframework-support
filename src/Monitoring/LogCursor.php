<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final readonly class LogCursor
{
    public function __construct(
        public string $path,
        public int $offset,
    ) {}
}
