<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final readonly class PendingErrorAlerts
{
    /**
     * @param  array<int, ErrorLogEntry>  $entries
     */
    public function __construct(
        public ?LogCursor $cursor,
        public array $entries,
        public bool $initialized = false,
    ) {}
}
