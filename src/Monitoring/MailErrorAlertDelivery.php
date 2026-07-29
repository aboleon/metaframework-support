<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

use Illuminate\Contracts\Mail\Factory;
use Illuminate\Support\Str;
use MetaFramework\Support\Monitoring\Contracts\ErrorAlertDelivery;
use RuntimeException;

final class MailErrorAlertDelivery implements ErrorAlertDelivery
{
    public function __construct(private readonly Factory $mail) {}

    public function send(array $entries): void
    {
        $recipient = ErrorAlertConfiguration::recipient();
        $mailer = trim((string) config('mfw-support.error_alerts.mailer'));
        $mailerName = $mailer !== '' ? $mailer : (string) config('mail.default');

        if ($this->usesLogTransport($mailerName)) {
            throw new RuntimeException('The Laravel log mail transport cannot deliver log error alerts.');
        }

        $this->mail
            ->mailer($mailer !== '' ? $mailer : null)
            ->raw($this->body($entries), function (object $message) use ($entries, $recipient): void {
                $message
                    ->to($recipient)
                    ->subject($this->subject($entries));
            });
    }

    /**
     * @param  array<int, ErrorLogEntry>  $entries
     */
    private function subject(array $entries): string
    {
        $environment = (string) config('app.env', 'unknown');
        $application = (string) config('app.name', 'Laravel');
        $count = count($entries);

        return "[{$environment}] {$application}: {$count} new Laravel error" . ($count === 1 ? '' : 's');
    }

    /**
     * @param  array<int, ErrorLogEntry>  $entries
     */
    private function body(array $entries): string
    {
        $maximumCharacters = max(
            500,
            (int) config('mfw-support.error_alerts.max_characters_per_entry', 8000),
        );
        $sections = array_map(
            static fn (ErrorLogEntry $entry): string => Str::limit(
                $entry->raw,
                $maximumCharacters,
                "\n[entry truncated]",
            ),
            $entries,
        );

        return implode("\n\n" . str_repeat('=', 72) . "\n\n", $sections);
    }

    /**
     * @param  array<int, string>  $visited
     */
    private function usesLogTransport(string $mailer, array $visited = []): bool
    {
        if ($mailer === '' || in_array($mailer, $visited, true)) {
            return false;
        }

        $configuration = config("mail.mailers.{$mailer}", []);

        if (!is_array($configuration)) {
            return false;
        }

        if (($configuration['transport'] ?? null) === 'log') {
            return true;
        }

        if (($configuration['transport'] ?? null) !== 'failover') {
            return false;
        }

        $visited[] = $mailer;

        foreach ((array) ($configuration['mailers'] ?? []) as $nestedMailer) {
            if ($this->usesLogTransport((string) $nestedMailer, $visited)) {
                return true;
            }
        }

        return false;
    }
}
