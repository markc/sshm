<?php

namespace App\Filament\Pages;

use App\Settings\SshSettings as SshSettingsModel;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SshSettings extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'SSH Settings';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.ssh-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SshSettingsModel::class);

        $this->form->fill([
            'home_dir' => $settings->getHomeDir(),
            'default_user' => $settings->getDefaultUser(),
            'default_port' => $settings->getDefaultPort(),
            'default_key_type' => $settings->getDefaultKeyType(),
            'strict_host_checking' => $settings->getStrictHostChecking(),
            'default_ssh_host' => $settings->getDefaultSshHost(),
            'default_ssh_key' => $settings->getDefaultSshKey(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('home_dir')
                            ->label('SSH Home Directory')
                            ->required()
                            ->placeholder('/home/user')
                            ->helperText('The home directory where the .ssh folder is located'),

                        TextInput::make('default_user')
                            ->label('Default SSH User')
                            ->required()
                            ->placeholder('root')
                            ->helperText('The default user for SSH connections'),

                        TextInput::make('default_port')
                            ->label('Default SSH Port')
                            ->required()
                            ->numeric()
                            ->placeholder('22')
                            ->helperText('The default port for SSH connections'),

                        TextInput::make('default_key_type')
                            ->label('Default SSH Key Type')
                            ->required()
                            ->placeholder('ed25519')
                            ->helperText('The default key type for new SSH keys'),

                        TextInput::make('default_ssh_host')
                            ->label('Default SSH Host')
                            ->placeholder('server.example.com')
                            ->helperText('Default SSH host to select in the SSH Commands page'),

                        TextInput::make('default_ssh_key')
                            ->label('Default SSH Key Path')
                            ->placeholder('/path/to/key')
                            ->helperText('Default SSH key path for connections'),

                        Toggle::make('strict_host_checking')
                            ->label('Strict Host Key Checking')
                            ->helperText('Enable strict host key checking for SSH connections')
                            ->columnSpan(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Debug: Log what data we're getting
            \Log::info('SSH Settings save data:', $data);

            // Get settings instance
            $settings = app(SshSettingsModel::class);

            // Save data
            $settings->save($data);

            // Refresh the application singleton
            app()->forgetInstance(SshSettingsModel::class);

            Notification::make()
                ->success()
                ->title('Settings saved successfully')
                ->body('All SSH settings have been updated and are now active.')
                ->send();
        } catch (\Exception $e) {
            \Log::error('SSH Settings save error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            Notification::make()
                ->danger()
                ->title('Error saving settings')
                ->body('There was an error saving the settings: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }
}
