<?php

declare(strict_types=1);

namespace MetaFramework\Support\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class ResponseMessages
{
    public static function parseResponse(mixed $response): string
    {
        if (is_string($response)) {
            return self::infoNotice($response);
        }

        if ($response instanceof RedirectResponse || ! is_array($response)) {
            return '';
        }

        $html = '';

        if (array_key_exists('messages', $response)) {
            foreach ($response['messages'] as $messages) {
                foreach ($messages as $type => $message) {
                    $html .= self::alertBox($message, $type);
                }
            }
            unset($response['messages']);
        }

        unset($response['error'], $response['abort']);

        if ($response && auth()->check() && auth()->user()->hasRole('dev')) {
            ob_start();
            foreach ($response as $key => $value) {
                d($value, $key);
            }
            $html .= ob_get_contents() ?: '';
            ob_end_clean();
        }

        return $html;
    }

    public static function validationErrors(MessageBag|ViewErrorBag|array $errors): string
    {
        if ($errors instanceof ViewErrorBag) {
            if (! $errors->any()) {
                return '';
            }

            return collect($errors->all())
                ->map(fn(string $message) => self::criticalNotice($message))
                ->implode('');
        }

        if ($errors instanceof MessageBag) {
            if (! $errors->any()) {
                return '';
            }

            return collect($errors->all())
                ->map(fn(string $message) => self::criticalNotice($message))
                ->implode('');
        }

        if (! is_array($errors)) {
            return '';
        }

        return collect($errors)
            ->map(fn(string $message) => self::criticalNotice($message))
            ->implode('');
    }

    public static function alertBox(string $message, string $type): string
    {
        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }

    public static function criticalNotice(string $message): string
    {
        return self::alertBox($message, 'danger');
    }

    public static function successNotice(string $message): string
    {
        return self::alertBox($message, 'success');
    }

    public static function warningNotice(string $message): string
    {
        return self::alertBox($message, 'warning');
    }

    public static function infoNotice(string $message): string
    {
        return self::alertBox($message, 'info');
    }
}
