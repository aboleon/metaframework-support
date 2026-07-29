<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Monitoring;

use MetaFramework\Support\Monitoring\LaravelLogParser;
use PHPUnit\Framework\TestCase;

class LaravelLogParserTest extends TestCase
{
    public function test_it_preserves_multiline_entries_and_uses_every_log_level_as_a_boundary(): void
    {
        $contents = <<<'LOG'
[2026-07-29 18:26:53] production.ERROR: Class "App\Models\Category" not found {"userId":1}
[stacktrace]
#0 /app/Models/GoogleMap.php(58): fetchContentTypes()
[2026-07-29 18:27:00] production.INFO: Request completed
[2026-07-29 18:28:00] production.CRITICAL: Database unavailable
second line
LOG;
        $records = (new LaravelLogParser)->parse($contents . "\n");

        $this->assertCount(3, $records);
        $this->assertSame('ERROR', $records[0]->level);
        $this->assertStringContainsString('#0 /app/Models/GoogleMap.php', $records[0]->raw);
        $this->assertStringNotContainsString('Request completed', $records[0]->raw);
        $this->assertSame('INFO', $records[1]->level);
        $this->assertSame('CRITICAL', $records[2]->level);
        $this->assertStringContainsString('second line', $records[2]->raw);
    }

    public function test_it_parses_crlf_logs_and_ignores_malformed_prefixes(): void
    {
        $records = (new LaravelLogParser)->parse(
            "not a log entry\r\n"
            . "[2026-07-29 18:26:53] staging.ALERT: Alert message\r\n"
            . "trace line\r\n",
        );

        $this->assertCount(1, $records);
        $this->assertSame('staging', $records[0]->environment);
        $this->assertSame('ALERT', $records[0]->level);
        $this->assertSame('Alert message', $records[0]->message);
        $this->assertStringContainsString("trace line\r\n", $records[0]->raw . "\r\n");
    }
}
