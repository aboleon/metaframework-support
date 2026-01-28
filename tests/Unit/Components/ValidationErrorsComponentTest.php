<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Components;

use MetaFramework\Support\Tests\TestCase;
use MetaFramework\Support\View\Components\ValidationErrors;

class ValidationErrorsComponentTest extends TestCase
{
    public function test_component_renders_validation_errors_view(): void
    {
        $component = new ValidationErrors;

        $view = $component->render();

        $this->assertSame('mfw-support::components.validation-errors', $view->name());
    }
}
