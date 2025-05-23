<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use App\Services\SshService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class SshCommandRunner extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    
    protected static ?string $navigationLabel = 'SSH Commands';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.ssh-command-runner';
    
    public ?array $commandOutput = null;
    
    public string $streamingOutput = '';
    
    public bool $isCommandRunning = false;
    
    public ?string $selectedHost = null;
    
    public ?string $command = null;
    
    public ?string $hostname = null;
    
    public ?string $port = '22';
    
    public ?string $username = 'root';
    
    public ?string $identityFile = null;
    
    public bool $useCustomConnection = false;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedHost')
                    ->label('Select SSH Host')
                    ->options(function () {
                        return SshHost::where('active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->hidden(fn () => $this->useCustomConnection)
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            $this->selectedHost = $state;
                        }
                    }),
                
                TextInput::make('hostname')
                    ->label('Hostname')
                    ->required()
                    ->hidden(fn () => !$this->useCustomConnection),
                
                TextInput::make('port')
                    ->label('Port')
                    ->numeric()
                    ->default('22')
                    ->hidden(fn () => !$this->useCustomConnection),
                
                TextInput::make('username')
                    ->label('Username')
                    ->default('root')
                    ->hidden(fn () => !$this->useCustomConnection),
                
                TextInput::make('identityFile')
                    ->label('Identity File (optional)')
                    ->placeholder('~/.ssh/id_ed25519')
                    ->hidden(fn () => !$this->useCustomConnection),
                
                Textarea::make('command')
                    ->label('Enter SSH Command(s)')
                    ->required()
                    ->rows(5)
                    ->placeholder('Enter SSH command(s) to execute...')
            ]);
    }
    
    protected function getFormModel(): string
    {
        return SshCommandRunner::class;
    }
    
    public function runCommand(): void
    {
        $this->validate([
            'command' => 'required|string',
            'selectedHost' => $this->useCustomConnection ? 'nullable' : 'required',
            'hostname' => $this->useCustomConnection ? 'required|string' : 'nullable',
            'port' => $this->useCustomConnection ? 'required|numeric' : 'nullable',
            'username' => $this->useCustomConnection ? 'required|string' : 'nullable',
        ]);
        
        // Reset output and set running state
        $this->streamingOutput = '';
        $this->commandOutput = null;
        $this->isCommandRunning = true;
        
        $sshService = app(SshService::class);
        
        try {
            if ($this->useCustomConnection) {
                // Create a temporary host for custom connection
                $tempHost = new SshHost([
                    'name' => 'temp_' . time(),
                    'hostname' => $this->hostname,
                    'port' => $this->port,
                    'user' => $this->username,
                    'identity_file' => $this->identityFile,
                ]);
                
                $result = $sshService->executeCommandWithStreaming(
                    $tempHost, 
                    $this->command,
                    function($type, $line) {
                        $this->streamingOutput .= $line;
                        $this->dispatch('outputUpdated', $this->streamingOutput);
                    }
                );
            } else {
                $host = SshHost::findOrFail($this->selectedHost);
                $result = $sshService->executeCommandWithStreaming(
                    $host, 
                    $this->command,
                    function($type, $line) {
                        $this->streamingOutput .= $line;
                        $this->dispatch('outputUpdated', $this->streamingOutput);
                    }
                );
            }
            
            $this->isCommandRunning = false;
            $this->commandOutput = $result;
            
            if ($result['success']) {
                Notification::make()
                    ->success()
                    ->title('Command executed successfully')
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title('Command failed')
                    ->body($result['error'])
                    ->send();
            }
        } catch (\Exception $e) {
            $this->isCommandRunning = false;
            $this->commandOutput = [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => -1,
            ];
            
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }
    
    public function toggleConnectionMode(): void
    {
        $this->useCustomConnection = !$this->useCustomConnection;
        $this->selectedHost = null;
        $this->hostname = null;
        $this->port = '22';
        $this->username = 'root';
        $this->identityFile = null;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('toggleConnection')
                ->label(fn () => $this->useCustomConnection ? 'Use Saved Host' : 'Use Custom Connection')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->toggleConnectionMode()),
        ];
    }
}
