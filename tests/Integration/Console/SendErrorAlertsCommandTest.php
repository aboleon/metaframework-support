<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Integration\Console;

use MetaFramework\Support\Monitoring\Contracts\ErrorAlertDelivery;
use MetaFramework\Support\Monitoring\ErrorAlertLock;
use MetaFramework\Support\Monitoring\ErrorLogEntry;
use MetaFramework\Support\Tests\TestCase;
use RuntimeException;

class SendErrorAlertsCommandTest extends TestCase
{
    private string $monitorDirectory;

    private RecordingErrorAlertDelivery $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitorDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'mfw-support-error-alerts-'
            . bin2hex(random_bytes(5));
        mkdir($this->monitorDirectory, 0775, true);

        config()->set('mfw-support.error_alerts.enabled', true);
        config()->set('mfw-support.error_alerts.recipient', 'alerts@example.com');
        config()->set('mfw-support.error_alerts.log_patterns', [
            $this->monitorDirectory . DIRECTORY_SEPARATOR . 'laravel*.log',
        ]);
        config()->set(
            'mfw-support.error_alerts.cursor_path',
            $this->monitorDirectory . DIRECTORY_SEPARATOR . 'cursor.json',
        );

        $this->delivery = new RecordingErrorAlertDelivery;
        $this->app->instance(ErrorAlertDelivery::class, $this->delivery);
    }

    protected function tearDown(): void
    {
        if (isset($this->monitorDirectory) && is_dir($this->monitorDirectory)) {
            foreach (glob($this->monitorDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
                unlink($path);
            }

            rmdir($this->monitorDirectory);
        }

        parent::tearDown();
    }

    public function test_it_initializes_at_eof_then_delivers_every_new_error_once(): void
    {
        $logPath = $this->logPath('2026-07-29');
        file_put_contents($logPath, $this->entry('INFO', 'Historical entry'));

        $this->artisan('mfw-support:send-error-alerts')
            ->expectsOutputToContain('cursor initialized')
            ->assertSuccessful();
        $this->assertSame([], $this->delivery->batches);

        file_put_contents(
            $logPath,
            $this->entry('ERROR', 'Class "App\\Models\\Category" not found', "#0 GoogleMap.php:58\n")
            . $this->entry('WARNING', 'Not alertable')
            . $this->entry('CRITICAL', 'Database unavailable'),
            FILE_APPEND,
        );

        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        $this->assertCount(1, $this->delivery->batches);
        $this->assertCount(2, $this->delivery->batches[0]);
        $this->assertSame('ERROR', $this->delivery->batches[0][0]->level);
        $this->assertStringContainsString('GoogleMap.php:58', $this->delivery->batches[0][0]->raw);
        $this->assertSame('CRITICAL', $this->delivery->batches[0][1]->level);

        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();
        $this->assertCount(1, $this->delivery->batches);
    }

    public function test_delivery_failure_keeps_the_cursor_for_retry(): void
    {
        $logPath = $this->logPath('2026-07-29');
        file_put_contents($logPath, $this->entry('INFO', 'Initial entry'));
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();
        $initialCursor = file_get_contents($this->cursorPath());

        file_put_contents($logPath, $this->entry('ERROR', 'Retry this failure'), FILE_APPEND);
        $this->delivery->fail = true;

        $this->artisan('mfw-support:send-error-alerts')->assertFailed();
        $this->assertSame($initialCursor, file_get_contents($this->cursorPath()));

        $this->delivery->fail = false;
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        $this->assertCount(1, $this->delivery->batches);
        $this->assertSame('Retry this failure', $this->delivery->batches[0][0]->message);
    }

    public function test_entry_limit_delivers_the_backlog_across_multiple_runs_without_loss(): void
    {
        config()->set('mfw-support.error_alerts.max_entries_per_email', 1);
        $logPath = $this->logPath('2026-07-29');
        file_put_contents($logPath, $this->entry('INFO', 'Initial entry'));
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        file_put_contents(
            $logPath,
            $this->entry('ERROR', 'First error')
            . $this->entry('ERROR', 'Second error'),
            FILE_APPEND,
        );

        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        $this->assertCount(2, $this->delivery->batches);
        $this->assertSame('First error', $this->delivery->batches[0][0]->message);
        $this->assertSame('Second error', $this->delivery->batches[1][0]->message);
    }

    public function test_concurrent_scan_exits_without_reading_or_advancing_the_cursor(): void
    {
        $logPath = $this->logPath('2026-07-29');
        file_put_contents($logPath, $this->entry('ERROR', 'Unprocessed error'));
        $lock = $this->app->make(ErrorAlertLock::class);
        $handle = $lock->acquire();

        try {
            $this->artisan('mfw-support:send-error-alerts')
                ->expectsOutputToContain('already running')
                ->assertSuccessful();
        } finally {
            $lock->release($handle);
        }

        $this->assertFileDoesNotExist($this->cursorPath());
        $this->assertSame([], $this->delivery->batches);
    }

    public function test_it_waits_for_an_incomplete_log_entry_and_finishes_the_rotated_file(): void
    {
        $firstLog = $this->logPath('2026-07-29');
        file_put_contents($firstLog, $this->entry('INFO', 'Initial entry'));
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        file_put_contents($firstLog, '[2026-07-29 23:59:59] production.ERROR: Incomplete', FILE_APPEND);
        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();
        $this->assertSame([], $this->delivery->batches);

        $secondLog = $this->logPath('2026-07-30');
        file_put_contents($secondLog, $this->entry('ERROR', 'New day error'));
        touch($secondLog, time() + 2);

        $this->artisan('mfw-support:send-error-alerts')->assertSuccessful();

        $this->assertCount(1, $this->delivery->batches);
        $this->assertSame(
            ['Incomplete', 'New day error'],
            array_map(
                static fn (ErrorLogEntry $entry): string => $entry->message,
                $this->delivery->batches[0],
            ),
        );
        $cursor = json_decode((string) file_get_contents($this->cursorPath()), true);
        $this->assertSame($secondLog, $cursor['path']);
        $this->assertSame(filesize($secondLog), $cursor['offset']);
    }

    private function logPath(string $date): string
    {
        return $this->monitorDirectory . DIRECTORY_SEPARATOR . "laravel-{$date}.log";
    }

    private function cursorPath(): string
    {
        return $this->monitorDirectory . DIRECTORY_SEPARATOR . 'cursor.json';
    }

    private function entry(string $level, string $message, string $continuation = ''): string
    {
        return "[2026-07-29 18:26:53] production.{$level}: {$message}\n{$continuation}";
    }
}

final class RecordingErrorAlertDelivery implements ErrorAlertDelivery
{
    /** @var array<int, array<int, ErrorLogEntry>> */
    public array $batches = [];

    public bool $fail = false;

    public function send(array $entries): void
    {
        if ($this->fail) {
            throw new RuntimeException('Mail transport unavailable.');
        }

        $this->batches[] = $entries;
    }
}
