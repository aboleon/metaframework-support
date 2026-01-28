<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Responses;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use MetaFramework\Support\Responses\ResponseMessages;
use MetaFramework\Support\Tests\TestCase;

class ResponseMessagesTest extends TestCase
{
    public function test_parse_response_returns_info_notice_for_string(): void
    {
        $html = ResponseMessages::parseResponse('Hello');

        $this->assertSame('<div class="alert alert-info">Hello</div>', $html);
    }

    public function test_parse_response_returns_empty_for_redirect_or_non_array(): void
    {
        $redirect = new RedirectResponse('/');

        $this->assertSame('', ResponseMessages::parseResponse($redirect));
        $this->assertSame('', ResponseMessages::parseResponse(123));
    }

    public function test_parse_response_renders_messages_and_dev_debug_output(): void
    {
        $this->app->instance('auth', new GuardStub(true, new class
        {
            public function hasRole(string $role): bool
            {
                return $role === 'dev';
            }
        }));

        $response = [
            'messages' => [
                ['success' => 'Saved'],
                ['warning' => 'Careful'],
            ],
            'payload' => ['id' => 10],
        ];

        $html = ResponseMessages::parseResponse($response);

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Saved', $html);
        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('Careful', $html);
        $this->assertStringContainsString('mfw-meta-parser', $html);
    }

    public function test_validation_errors_for_message_bags_and_arrays(): void
    {
        $bag = new MessageBag(['First error', 'Second error']);
        $viewBag = new ViewErrorBag;
        $viewBag->put('default', $bag);

        $this->assertStringContainsString('alert-danger', ResponseMessages::validationErrors($bag));
        $this->assertStringContainsString('First error', ResponseMessages::validationErrors($viewBag));
        $this->assertSame('', ResponseMessages::validationErrors(new MessageBag));

        $html = ResponseMessages::validationErrors(['Array error']);
        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Array error', $html);
    }
}

class GuardStub implements Guard
{
    public function __construct(
        private bool $checked,
        private ?object $currentUser = null
    ) {}

    public function check(): bool
    {
        return $this->checked;
    }

    public function guest(): bool
    {
        return !$this->checked;
    }

    public function user()
    {
        return $this->currentUser;
    }

    public function hasUser(): bool
    {
        return $this->currentUser !== null;
    }

    public function id()
    {
        return null;
    }

    public function validate(array $credentials = [])
    {
        return false;
    }

    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->currentUser = $user;

        return $this;
    }
}
