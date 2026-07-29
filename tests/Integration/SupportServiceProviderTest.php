<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Integration;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use MetaFramework\Support\Tests\TestCase;

class SupportServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_views_and_components(): void
    {
        $html = Blade::render('<x-mfw-support::alert message="Notice" />');

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Notice', $html);
    }

    public function test_service_provider_loads_translations(): void
    {
        $translation = __('mfw-support::mfw-support.ajax.request_cannot_be_interpreted');

        $this->assertSame('This request cannot be interpreted.', $translation);
    }

    public function test_service_provider_schedules_enabled_error_alert_monitor(): void
    {
        config()->set('mfw-support.error_alerts.enabled', true);
        config()->set('mfw-support.error_alerts.schedule.enabled', true);
        config()->set('mfw-support.error_alerts.schedule.cron', '*/2 * * * *');

        $events = $this->app->make(Schedule::class)->events();
        $event = collect($events)->first(
            static fn (object $event): bool => str_contains(
                (string) $event->command,
                'mfw-support:send-error-alerts',
            ),
        );

        $this->assertNotNull($event);
        $this->assertSame('*/2 * * * *', $event->expression);
    }

    public function test_service_provider_does_not_schedule_disabled_error_alert_monitor(): void
    {
        config()->set('mfw-support.error_alerts.enabled', false);

        $events = $this->app->make(Schedule::class)->events();

        $this->assertFalse(collect($events)->contains(
            static fn (object $event): bool => str_contains(
                (string) $event->command,
                'mfw-support:send-error-alerts',
            ),
        ));
    }
}
