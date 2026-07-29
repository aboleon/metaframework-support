<?php

declare(strict_types=1);

namespace MetaFramework\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MetaFramework\Support\Console\SendErrorAlertsCommand;
use MetaFramework\Support\Monitoring\Contracts\ErrorAlertDelivery;
use MetaFramework\Support\Monitoring\MailErrorAlertDelivery;

class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Resources/config/mfw-support.php', 'mfw-support');

        $this->app->singleton(ErrorAlertDelivery::class, MailErrorAlertDelivery::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'mfw-support');
        Blade::componentNamespace('MetaFramework\\Support\\View\\Components', 'mfw-support');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../publishable/lang', 'mfw-support');

        if ($this->app->runningInConsole()) {
            // Publish AJAX JavaScript file
            $this->publishes([
                __DIR__ . '/../publishable/public/js/mfw-ajax.js' => public_path('vendor/mfw-support/js/mfw-ajax.js'),
                __DIR__ . '/../publishable/public/js/mfw-action-client.js' => public_path('vendor/mfw-support/js/mfw-action-client.js'),
            ], 'mfw-support-assets');

            // Publish translations
            $this->publishes([
                __DIR__ . '/../publishable/lang' => $this->app->langPath('vendor/mfw-support'),
            ], 'mfw-support-translations');

            $this->publishes([
                __DIR__ . '/Resources/config/mfw-support.php' => config_path('mfw-support.php'),
            ], 'mfw-support-config');

            // Publish everything
            $this->publishes([
                __DIR__ . '/../publishable/public/js/mfw-ajax.js' => public_path('vendor/mfw-support/js/mfw-ajax.js'),
                __DIR__ . '/../publishable/public/js/mfw-action-client.js' => public_path('vendor/mfw-support/js/mfw-action-client.js'),
                __DIR__ . '/../publishable/lang' => $this->app->langPath('vendor/mfw-support'),
                __DIR__ . '/Resources/config/mfw-support.php' => config_path('mfw-support.php'),
            ], 'mfw-support');

            // Register commands
            $this->commands([
                Console\PublishAssetsCommand::class,
                Console\PublishTranslationsCommand::class,
                SendErrorAlertsCommand::class,
            ]);

            $this->registerErrorAlertSchedule();
        }
    }

    private function registerErrorAlertSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (!config('mfw-support.error_alerts.enabled')
                || !config('mfw-support.error_alerts.schedule.enabled')) {
                return;
            }

            $event = $schedule
                ->command('mfw-support:send-error-alerts')
                ->cron((string) config('mfw-support.error_alerts.schedule.cron', '* * * * *'))
                ->withoutOverlapping((int) config('mfw-support.error_alerts.schedule.lock_minutes', 5));

            if (config('mfw-support.error_alerts.schedule.on_one_server')) {
                $event->onOneServer();
            }
        });
    }
}
