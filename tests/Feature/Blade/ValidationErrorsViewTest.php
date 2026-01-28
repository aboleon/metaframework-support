<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Feature\Blade;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use MetaFramework\Support\Tests\TestCase;

class ValidationErrorsViewTest extends TestCase
{
    public function test_validation_errors_view_renders_messages_when_errors_present(): void
    {
        $bag = new MessageBag(['First error']);
        $viewBag = new ViewErrorBag;
        $viewBag->put('default', $bag);

        $html = view('mfw-support::components.validation-errors', [
            'errors' => $viewBag,
        ])->render();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('First error', $html);
    }

    public function test_validation_errors_view_is_empty_when_no_errors(): void
    {
        $viewBag = new ViewErrorBag;

        $html = view('mfw-support::components.validation-errors', [
            'errors' => $viewBag,
        ])->render();

        $this->assertSame('', trim($html));
    }
}
