<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Feature\Blade;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use MetaFramework\Support\Tests\TestCase;

class ValidationBannerViewTest extends TestCase
{
    public function test_validation_banner_view_renders_when_errors_present(): void
    {
        $bag = new MessageBag(['First error']);
        $viewBag = new ViewErrorBag;
        $viewBag->put('default', $bag);

        $html = view('mfw-support::components.validation-banner', [
            'errors' => $viewBag,
        ])->render();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('mfw.validation-banner', $html);
    }

    public function test_validation_banner_view_is_empty_when_no_errors(): void
    {
        $viewBag = new ViewErrorBag;

        $html = view('mfw-support::components.validation-banner', [
            'errors' => $viewBag,
        ])->render();

        $this->assertSame('', trim($html));
    }
}
