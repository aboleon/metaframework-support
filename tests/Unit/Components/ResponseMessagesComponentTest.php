<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Components;

use MetaFramework\Support\Tests\TestCase;
use MetaFramework\Support\View\Components\ResponseMessages;

class ResponseMessagesComponentTest extends TestCase
{
    public function test_component_renders_response_messages_view(): void
    {
        $component = new ResponseMessages;

        $view = $component->render();

        $this->assertSame('mfw-support::components.response-messages', $view->name());
        $this->assertSame('mfw-messages', $component->id);
        $this->assertSame('', $component->ajax);
    }

    public function test_component_accepts_custom_id_and_ajax_target(): void
    {
        $component = new ResponseMessages('custom-id', 'api/messages');

        $this->assertSame('custom-id', $component->id);
        $this->assertSame('api/messages', $component->ajax);
    }
}
