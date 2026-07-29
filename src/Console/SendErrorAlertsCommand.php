<?php

declare(strict_types=1);

namespace MetaFramework\Support\Console;

use Illuminate\Console\Command;
use MetaFramework\Support\Monitoring\Contracts\ErrorAlertDelivery;
use MetaFramework\Support\Monitoring\ErrorAlertConfiguration;
use MetaFramework\Support\Monitoring\ErrorAlertLock;
use MetaFramework\Support\Monitoring\ErrorLogScanner;
use Throwable;

final class SendErrorAlertsCommand extends Command
{
    protected $signature = 'mfw-support:send-error-alerts';

    protected $description = 'Email newly logged Laravel error entries';

    public function handle(
        ErrorLogScanner $scanner,
        ErrorAlertDelivery $delivery,
        ErrorAlertLock $lock,
    ): int {
        if (!$this->configured()) {
            $this->components->info('Laravel error email alerts are disabled.');

            return self::SUCCESS;
        }

        $lockHandle = null;

        try {
            $lockHandle = $lock->acquire();

            if ($lockHandle === null) {
                $this->components->info('Another Laravel error alert scan is already running.');

                return self::SUCCESS;
            }

            return $this->processPendingAlerts($scanner, $delivery);
        } catch (Throwable $throwable) {
            $this->components->error("Unable to process Laravel error alerts: {$throwable->getMessage()}");

            return self::FAILURE;
        } finally {
            $lock->release($lockHandle);
        }
    }

    private function processPendingAlerts(
        ErrorLogScanner $scanner,
        ErrorAlertDelivery $delivery,
    ): int {
        $pending = $scanner->scan();

        if ($pending->cursor === null) {
            $this->components->info('No Laravel log file is available.');

            return self::SUCCESS;
        }

        if ($pending->entries === []) {
            $scanner->commit($pending->cursor);
            $this->components->info(
                $pending->initialized
                    ? 'Laravel error alert cursor initialized.'
                    : 'No new Laravel errors found.',
            );

            return self::SUCCESS;
        }

        $delivery->send($pending->entries);
        $scanner->commit($pending->cursor);
        $this->components->info(count($pending->entries) . ' Laravel error alert(s) delivered.');

        return self::SUCCESS;
    }

    private function configured(): bool
    {
        return (bool) config('mfw-support.error_alerts.enabled')
            && ErrorAlertConfiguration::recipient() !== '';
    }
}
