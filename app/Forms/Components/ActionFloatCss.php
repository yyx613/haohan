<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Component;

class ActionFloatCss extends Component
{
    protected string $view = 'forms.components.action-float-css';

    public static function make(): static
    {
        return app(static::class);
    }
}
