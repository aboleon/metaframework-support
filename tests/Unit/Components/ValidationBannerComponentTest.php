<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Components;

use MetaFramework\Support\Tests\TestCase;
use MetaFramework\Support\View\Components\ValidationBanner;

class ValidationBannerComponentTest extends TestCase
{
    public function test_component_renders_validation_banner_view(): void
    {
        $component = new ValidationBanner;

        $view = $component->render();

        $this->assertSame('mfw-support::components.validation-banner', $view->name());
    }
}
