<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class MarkdownWidget extends Widget
{
    protected static string $view = 'filament.widgets.markdown-widget';
    
    // Ensure the widget is at the full width
    protected int | string | array $columnSpan = 'full';
    
    // Make sure this widget goes below any others that might exist
    protected static ?int $sort = 30;
    
    // Allow fullscreen mode
    protected static bool $isLazy = false;
    
    public function getContent(): string
    {
        $path = base_path('README.md');
        
        if (!File::exists($path)) {
            return 'README.md not found.';
        }

        $markdown = File::get($path);
        
        // Use the simplified GithubFlavoredMarkdownConverter
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => true,
            'max_nesting_level' => 100,
        ]);
        
        // Convert the markdown to HTML
        return $converter->convert($markdown)->getContent();
    }
}