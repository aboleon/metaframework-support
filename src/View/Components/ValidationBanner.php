<?php

declare(strict_types=1);

namespace MetaFramework\Support\View\Components;

use Illuminate\View\Component;

class ValidationBanner extends Component
{
    public function __construct()
    {
        //
    }

    public function render()
    {
        return view('mfw-support::components.validation-banner');
    }
}
