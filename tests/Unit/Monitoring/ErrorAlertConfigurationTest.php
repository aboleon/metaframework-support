<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Monitoring;

use MetaFramework\Support\Monitoring\ErrorAlertConfiguration;
use MetaFramework\Support\Tests\TestCase;

class ErrorAlertConfigurationTest extends TestCase
{
    public function test_it_uses_laravels_default_mail_address_when_no_alert_recipient_is_configured(): void
    {
        config()->set('mfw-support.error_alerts.recipient');
        config()->set('mail.from.address', 'default@example.com');

        $this->assertSame('default@example.com', ErrorAlertConfiguration::recipient());
    }

    public function test_dedicated_alert_recipient_overrides_laravels_default_mail_address(): void
    {
        config()->set('mfw-support.error_alerts.recipient', 'alerts@example.com');
        config()->set('mail.from.address', 'default@example.com');

        $this->assertSame('alerts@example.com', ErrorAlertConfiguration::recipient());
    }
}
