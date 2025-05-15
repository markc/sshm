<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // Make this page appear first in navigation
    protected static ?int $navigationSort = -2;

    // Override the default view with our custom view
    protected static string $view = 'filament.pages.dashboard';

    /**
     * Get the README.md content as HTML for preview
     */
    public function getReadmeContent(): string
    {
        $path = base_path('README.md');

        if (! File::exists($path)) {
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

    /**
     * Disable widgets entirely on the dashboard
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    /**
     * Disable footer widgets
     */
    protected function getFooterWidgets(): array
    {
        return [];
    }
}
