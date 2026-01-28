<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use MetaFramework\Support\Services\ResponseParser;
use MetaFramework\Support\Tests\TestCase;

class ResponseParserTest extends TestCase
{
    public function test_parse_response_returns_info_notice_for_string(): void
    {
        $html = ResponseParser::parseResponse('Hello');

        $this->assertSame('<div class="alert alert-info">Hello</div>', $html);
    }

    public function test_parse_response_returns_empty_for_redirect_or_non_array(): void
    {
        $redirect = new RedirectResponse('/');

        $this->assertSame('', ResponseParser::parseResponse($redirect));
        $this->assertSame('', ResponseParser::parseResponse(123));
    }

    public function test_parse_response_hides_debug_messages_for_non_dev(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        config(['app.debug' => false]);

        $response = [
            'messages' => [
                ['debug' => 'Hidden'],
                ['success' => 'Visible'],
            ],
            'restricted_to_dev' => true,
        ];

        $html = ResponseParser::parseResponse($response);

        $this->assertStringContainsString('Visible', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function test_parse_response_shows_debug_messages_when_app_debug_and_not_restricted(): void
    {
        Auth::shouldReceive('check')->andReturn(false);
        config(['app.debug' => true]);

        $response = [
            'messages' => [
                ['debug' => 'Debugging'],
            ],
        ];

        $html = ResponseParser::parseResponse($response);

        $this->assertStringContainsString('alert-light', $html);
        $this->assertStringContainsString('Debugging', $html);
    }

    public function test_validation_errors_outputs_critical_notices(): void
    {
        $errors = new MessageBag(['First', 'Second']);

        ob_start();
        ResponseParser::validationErrors($errors);
        $output = ob_get_clean();

        $this->assertStringContainsString('alert-danger', $output);
        $this->assertStringContainsString('First', $output);
    }
}
