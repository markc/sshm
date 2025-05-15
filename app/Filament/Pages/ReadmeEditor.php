<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class ReadmeEditor extends Page
{
    use InteractsWithForms;

    // Set navigation properties
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'README Editor';
    protected static ?string $title = 'README Editor';
    protected static ?int $navigationSort = -1; // Make it appear after Dashboard but before other items
    
    // Specify the view
    protected static string $view = 'filament.pages.readme-editor';
    
    // Form data
    public array $data = [];
    
    /**
     * Called when component is initialized
     */
    public function mount(): void
    {
        $this->form->fill([
            'markdownContent' => $this->getReadmeFileContents(),
        ]);
    }
    
    /**
     * Configure the form with Filament's native components
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make()
                    ->schema([
                        Textarea::make('markdownContent')
                            ->label(false) // No label needed as it's in the section heading
                            ->required(false) // Remove the required asterisk
                            ->columnSpanFull()
                            ->rows(20)
                            ->extraInputAttributes([
                                'class' => 'font-mono', 
                                'style' => 'white-space: pre; font-family: monospace;'
                            ])
                            ->helperText('Changes are not saved until you click the Save Changes button.'),
                    ])
                    ->heading('Edit README.md')
                    ->headerActions([
                        \Filament\Forms\Components\Actions\Action::make('save')
                            ->label('Save Changes')
                            ->color('primary')
                            ->icon('heroicon-o-document-check')
                            ->action('save')
                            ->size('sm'),
                            
                        \Filament\Forms\Components\Actions\Action::make('push')
                            ->label('Push Changes')
                            ->color('success')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->action('pushToGithub')
                            ->size('sm')
                    ])
                    ->collapsible(false)
            ])
            ->statePath('data');
    }
    
    /**
     * Get raw README.md content
     */
    private function getReadmeFileContents(): string
    {
        $path = base_path('README.md');
        
        if (File::exists($path)) {
            return File::get($path);
        }
        
        return '# Project Documentation' . PHP_EOL . PHP_EOL . 'Add your documentation here.';
    }
    
    /**
     * Save the README.md content
     */
    public function save(): void
    {
        $data = $this->form->getState();
        
        File::put(base_path('README.md'), $data['markdownContent']);
        
        Notification::make()
            ->title('README.md updated successfully')
            ->success()
            ->send();
        
        // Refresh the form to update the preview
        $this->form->fill([
            'markdownContent' => $this->getReadmeFileContents(),
        ]);
    }

    /**
     * Push README changes to GitHub
     */
    public function pushToGithub(): void
    {
        try {
            // First save any pending changes to ensure everything is committed
            $this->save();
            
            // Generate a descriptive commit message
            $commitMessage = "Update README documentation: " . date('Y-m-d H:i:s');
            
            // Execute git commands
            $basePath = base_path();
            $command = "cd {$basePath} && git add . && git commit -a -m \"{$commitMessage}\" && git push 2>&1";
            $output = shell_exec($command);
            
            // Check if the execution was successful
            if (strpos($output, 'error:') !== false || strpos($output, 'fatal:') !== false) {
                throw new \Exception("Git error: " . $output);
            }
            
            // Send a success notification
            Notification::make()
                ->title('Changes pushed to GitHub')
                ->body('README.md changes have been committed and pushed to the repository.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Handle any errors
            Notification::make()
                ->title('Error pushing to GitHub')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Get the README.md content as HTML for preview
     */
    public function getReadmeContent(): string
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
