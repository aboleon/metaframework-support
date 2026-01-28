<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Feature\Blade;

use MetaFramework\Support\Tests\TestCase;

class ResponseMessagesViewTest extends TestCase
{
    public function test_response_messages_view_renders_session_messages_and_clears_session(): void
    {
        session()->put('session_response', [
            'messages' => [
                ['success' => 'Saved'],
            ],
        ]);

        $html = view('mfw-support::components.response-messages', [
            'id' => 'test-messages',
            'ajax' => '',
        ])->render();

        $this->assertStringContainsString('test-messages', $html);
        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Saved', $html);
        $this->assertFalse(session()->has('session_response'));
    }
}
