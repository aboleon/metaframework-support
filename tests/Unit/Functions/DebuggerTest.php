<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Functions;

use MetaFramework\Support\Tests\TestCase;

class DebuggerTest extends TestCase
{
    public function test_d_outputs_debug_markup_for_string(): void
    {
        ob_start();
        d('value', 'label');
        $output = ob_get_clean();

        $this->assertStringContainsString('mfw-meta-parser', $output);
        $this->assertStringContainsString('label', $output);
        $this->assertStringContainsString('value', $output);
    }
}
