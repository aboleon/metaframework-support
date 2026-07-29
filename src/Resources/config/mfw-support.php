<?php

declare(strict_types=1);

return [
    'error_alerts' => [
        'enabled' => env('MFW_ERROR_ALERTS_ENABLED', false),
        'recipient' => env('MFW_ERROR_ALERTS_EMAIL'),
        'mailer' => env('MFW_ERROR_ALERTS_MAILER'),
        'log_patterns' => [
            'logs/laravel*.log',
        ],
        'cursor_path' => 'app/mfw-support/error-alert-cursor.json',
        'levels' => [
            'EMERGENCY',
            'ALERT',
            'CRITICAL',
            'ERROR',
        ],
        'max_entries_per_email' => 20,
        'max_characters_per_entry' => 8000,
        'schedule' => [
            'enabled' => true,
            'cron' => '* * * * *',
            'lock_minutes' => 5,
            'on_one_server' => false,
        ],
    ],
];
