<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring\Contracts;

use MetaFramework\Support\Monitoring\ErrorLogEntry;

interface ErrorAlertDelivery
{
    /**
     * @param  array<int, ErrorLogEntry>  $entries
     */
    public function send(array $entries): void;
}
