<?php

declare(strict_types=1);

namespace MetaFramework\Support\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use JetBrains\PhpStorm\Pure;

class ResponseParser
{
    public static function parseResponse($response): string
    {
        $isConnectedAsDev = Auth::check(); // && Auth::user()->hasRole('dev');

        $html = '';

        if (is_string($response)) {
            return self::infoNotice($response);
        }

        if ($response instanceof RedirectResponse) {
            return $html;
        }

        if (!is_array($response)) {
            return $html;
        }

        if (array_key_exists('messages', $response)) {
            foreach ($response['messages'] as $val) {
                foreach ($val as $key => $message) {
                    $show_debug = false;
                    $class      = $key;
                    if ($key == 'debug') {
                        $class = 'light';
                        if ($isConnectedAsDev || (config('app.debug') && empty($response['restricted_to_dev']))) {
                            $show_debug = true;
                        }
                    }
                    if ($key == 'debug' && !$show_debug) {
                        continue;
                    }
                    $html .= self::alertBox($message, $class);
                }
            }
            unset($response['messages']);
        }

        unset($response['error'], $response['abort']);

        return $html;
    }

    public static function validationErrors($errors): void
    {
        if ($errors->any()) {
            foreach ($errors->all() as $error) {
                echo self::criticalNotice($error);
            }
        }
    }

    public static function alertBox(string $message, string $class): string
    {
        return '<div class="alert alert-' . $class . '">' . $message . '</div>';
    }

    #[Pure]
    public static function criticalNotice(string $message): string
    {
        return self::alertBox($message, 'danger');
    }

    #[Pure]
    public static function successNotice(string $message): string
    {
        return self::alertBox($message, 'success');
    }

    #[Pure]
    public static function warningNotice(string $message): string
    {
        return self::alertBox($message, 'warning');
    }

    #[Pure]
    public static function infoNotice(string $message): string
    {
        return self::alertBox($message, 'info');
    }
}
