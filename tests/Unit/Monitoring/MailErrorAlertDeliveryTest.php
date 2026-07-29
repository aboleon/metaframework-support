<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Monitoring;

use Illuminate\Contracts\Mail\Factory;
use Illuminate\Contracts\Mail\Mailer;
use MetaFramework\Support\Monitoring\ErrorLogEntry;
use MetaFramework\Support\Monitoring\MailErrorAlertDelivery;
use MetaFramework\Support\Tests\TestCase;
use RuntimeException;

class MailErrorAlertDeliveryTest extends TestCase
{
    public function test_it_sends_a_plain_text_digest_through_the_configured_mailer(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.name', 'Example');
        config()->set('mfw-support.error_alerts.recipient', 'alerts@example.com');
        config()->set('mfw-support.error_alerts.mailer', 'smtp-alerts');
        config()->set('mail.mailers.smtp-alerts.transport', 'smtp');

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())
            ->method('raw')
            ->with(
                $this->callback(
                    static fn (string $body): bool => str_contains($body, 'Representative failure')
                        && str_contains($body, '#0 stack trace'),
                ),
                $this->callback(function (callable $callback): bool {
                    $message = new RecordingMailMessage;
                    $callback($message);

                    return $message->recipient === 'alerts@example.com'
                        && $message->subject === '[production] Example: 1 new Laravel error';
                }),
            );
        $factory = $this->createMock(Factory::class);
        $factory->expects($this->once())
            ->method('mailer')
            ->with('smtp-alerts')
            ->willReturn($mailer);

        (new MailErrorAlertDelivery($factory))->send([
            new ErrorLogEntry(
                timestamp: '2026-07-29 18:26:53',
                environment: 'production',
                level: 'ERROR',
                message: 'Representative failure',
                raw: "[2026-07-29 18:26:53] production.ERROR: Representative failure\n#0 stack trace",
            ),
        ]);
    }

    public function test_it_rejects_log_transport_including_inside_failover(): void
    {
        config()->set('mfw-support.error_alerts.recipient', 'alerts@example.com');
        config()->set('mfw-support.error_alerts.mailer', 'resilient');
        config()->set('mail.mailers.resilient', [
            'transport' => 'failover',
            'mailers' => ['smtp', 'log'],
        ]);
        config()->set('mail.mailers.smtp.transport', 'smtp');
        config()->set('mail.mailers.log.transport', 'log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('log mail transport');

        (new MailErrorAlertDelivery($this->createStub(Factory::class)))->send([
            new ErrorLogEntry('', '', 'ERROR', 'Failure', 'Failure'),
        ]);
    }
}

final class RecordingMailMessage
{
    public string $recipient = '';

    public string $subject = '';

    public function to(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
