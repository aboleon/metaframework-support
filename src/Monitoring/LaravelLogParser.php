<?php

declare(strict_types=1);

namespace MetaFramework\Support\Monitoring;

final class LaravelLogParser
{
    private const HEADER_PATTERN = '/^\[(?<timestamp>[^\]]+)]\s+(?<environment>[^\s.]+)\.(?<level>[A-Z]+):\s*(?<message>[^\r\n]*)/m';

    /**
     * @return array<int, ParsedLogRecord>
     */
    public function parse(string $contents): array
    {
        preg_match_all(self::HEADER_PATTERN, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $records = [];
        foreach ($matches as $index => $match) {
            $startOffset = $match[0][1];
            $endOffset = $matches[$index + 1][0][1] ?? strlen($contents);

            $records[] = new ParsedLogRecord(
                timestamp: $match['timestamp'][0],
                environment: $match['environment'][0],
                level: $match['level'][0],
                message: $match['message'][0],
                raw: rtrim(substr($contents, $startOffset, $endOffset - $startOffset)),
                endOffset: $endOffset,
            );
        }

        return $records;
    }
}
