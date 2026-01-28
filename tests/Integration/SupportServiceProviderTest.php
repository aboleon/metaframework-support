<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Integration;

use Illuminate\Support\Facades\Blade;
use MetaFramework\Support\Tests\TestCase;

class SupportServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_views_and_components(): void
    {
        $html = Blade::render('<x-mfw-support::alert message="Notice" />');

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Notice', $html);
    }

    public function test_service_provider_loads_translations(): void
    {
        $translation = __('mfw-support::mfw-support.ajax.request_cannot_be_interpreted');

        $this->assertSame('This request cannot be interpreted.', $translation);
    }
}
