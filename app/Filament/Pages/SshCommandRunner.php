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
    
    public bool $verboseDebug = false;
    
    public string $debugOutput = '';
    
    public bool $useBash = false;
    
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
                \Filament\Forms\Components\Grid::make(2)
                    ->schema([
                        // Left side - 50% width for command textarea
                        \Filament\Forms\Components\Group::make([
                            Textarea::make('command')
                                ->label('Enter SSH Command(s)')
                                ->required()
                                ->rows(8)
                                ->placeholder('Enter SSH command(s) to execute...')
                                ->extraAttributes(['style' => 'resize: none;'])
                        ])
                        ->columnSpan(1),
                        
                        // Right side - 50% width for controls
                        \Filament\Forms\Components\Group::make([
                            // SSH Host selector and Run button on same line
                            \Filament\Forms\Components\Grid::make(2)
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
                                        })
                                        ->columnSpan(1),
                                    
                                    \Filament\Forms\Components\Group::make([
                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('runCommand')
                                                ->label(fn () => $this->isCommandRunning ? 'Running...' : 'Run Command')
                                                ->disabled(fn () => $this->isCommandRunning)
                                                ->icon(fn () => $this->isCommandRunning ? 'heroicon-o-arrow-path' : 'heroicon-o-play')
                                                ->iconPosition('before')
                                                ->color('primary')
                                                ->size('lg')
                                                ->extraAttributes(fn () => $this->isCommandRunning ? ['class' => 'animate-pulse'] : [])
                                                ->action(function () {
                                                    $this->runCommand();
                                                })
                                                ->requiresConfirmation(false)
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full mt-6'])
                                        ])
                                    ])
                                    ->columnSpan(1)
                                ]),
                            
                            // Custom connection fields (stacked vertically in the right column)
                            TextInput::make('hostname')
                                ->label('Hostname')
                                ->required()
                                ->hidden(fn () => !$this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),
                            
                            TextInput::make('port')
                                ->label('Port')
                                ->numeric()
                                ->default('22')
                                ->hidden(fn () => !$this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),
                            
                            TextInput::make('username')
                                ->label('Username')
                                ->default('root')
                                ->hidden(fn () => !$this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),
                            
                            TextInput::make('identityFile')
                                ->label('Identity File (optional)')
                                ->placeholder('~/.ssh/id_ed25519')
                                ->hidden(fn () => !$this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-4']),
                            
                            \Filament\Forms\Components\Toggle::make('verboseDebug')
                                ->label('Verbose Debug')
                                ->inline(true),
                            
                            \Filament\Forms\Components\Toggle::make('useBash')
                                ->label('Use bash')
                                ->inline(true)
                        ])
                        ->columnSpan(1)
                    ])
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
        $this->debugOutput = '';
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
                    },
                    $this->verboseDebug,
                    function($debugLine) {
                        $this->debugOutput .= $debugLine . "\n";
                        $this->dispatch('debugUpdated', $this->debugOutput);
                    },
                    $this->useBash
                );
            } else {
                $host = SshHost::findOrFail($this->selectedHost);
                $result = $sshService->executeCommandWithStreaming(
                    $host, 
                    $this->command,
                    function($type, $line) {
                        $this->streamingOutput .= $line;
                        $this->dispatch('outputUpdated', $this->streamingOutput);
                    },
                    $this->verboseDebug,
                    function($debugLine) {
                        $this->debugOutput .= $debugLine . "\n";
                        $this->dispatch('debugUpdated', $this->debugOutput);
                    },
                    $this->useBash
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
