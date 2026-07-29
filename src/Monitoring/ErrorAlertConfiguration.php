<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final class ErrorAlertConfiguration
{
    public static function recipient(): string
    {
        return trim((string) (
            config('mfw-support.error_alerts.recipient')
            ?: config('mail.from.address')
        ));
    }
}
