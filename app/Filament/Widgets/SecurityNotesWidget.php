<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SecurityNotesWidget extends Widget
{
    protected static string $view = 'filament.widgets.security-notes';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
}