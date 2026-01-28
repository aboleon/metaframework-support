<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Feature\Blade;

use Illuminate\Support\Facades\Blade;
use MetaFramework\Support\Tests\TestCase;

class AlertComponentTest extends TestCase
{
    public function test_alert_component_renders_with_type_and_class(): void
    {
        $html = Blade::render('<x-mfw-support::alert message="Hello" type="success" class="mb-2" />');

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('mb-2', $html);
        $this->assertStringContainsString('Hello', $html);
    }
}
