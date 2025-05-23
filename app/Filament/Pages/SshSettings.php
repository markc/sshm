<?php

namespace App\Filament\Pages;

use App\Settings\SshSettings as SshSettingsModel;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SshSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationLabel = 'SSH Settings';
    
    protected static ?int $navigationSort = 5;
    
    protected static string $view = 'filament.pages.ssh-settings';
    
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
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
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
                    
                Toggle::make('strict_host_checking')
                    ->label('Strict Host Key Checking')
                    ->helperText('Enable strict host key checking for SSH connections'),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Get settings instance
        $settings = app(SshSettingsModel::class);
        
        // Save data
        $settings->save($data);
        
        // Refresh the application singleton
        app()->forgetInstance(SshSettingsModel::class);
        
        Notification::make()
            ->success()
            ->title('Settings saved successfully')
            ->send();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
