<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

/**
 * Ultra-Fast Xterm.js WebSocket SSH Terminal
 * 
 * Performance-optimized SSH terminal using:
 * - Xterm.js with GPU acceleration (WebGL renderer)
 * - WebSocket for bidirectional real-time communication
 * - Zero-latency input/output streaming
 * - Professional terminal emulation
 */
class XtermSshTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.xterm-ssh-terminal';
    protected static ?string $navigationLabel = 'SSH Terminal';
    protected static ?int $navigationSort = 1;

    // Form state
    public ?int $selectedHost = null;
    public string $command = '';
    public bool $useBash = false;
    public bool $showDebug = false;

    // Terminal state
    public bool $isConnected = false;
    public ?string $sessionId = null;

    /**
     * Configure the page layout
     */
    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    /**
     * Mount the page with default values
     */
    public function mount(): void
    {
        // Set default host if only one exists
        $hosts = SshHost::where('active', true)->get();
        if ($hosts->count() === 1) {
            $this->selectedHost = $hosts->first()->id;
        }

        // Set default command from settings
        $settings = app(\App\Settings\SshSettings::class);
        $this->command = $settings->getDefaultCommand() ?? 'ls -al';
    }

    /**
     * Configure the form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->schema([
                    // Left column: Command input
                    Grid::make(1)
                        ->schema([
                            Textarea::make('command')
                                ->label('SSH Command')
                                ->placeholder('Enter your SSH command...')
                                ->rows(8)
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(1),
                    
                    // Right column: Controls
                    Grid::make(1)
                        ->schema([
                            Select::make('selectedHost')
                                ->label('SSH Host')
                                ->options(SshHost::where('active', true)->pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->searchable(),
                            
                            Grid::make(2)
                                ->schema([
                                    Toggle::make('useBash')
                                        ->label('Use Bash')
                                        ->helperText('Execute with bash -ci for aliases and functions')
                                        ->live()
                                        ->inline(false),
                                    
                                    Toggle::make('showDebug')
                                        ->label('Debug Mode')
                                        ->helperText('Show performance metrics and debug info')
                                        ->live()
                                        ->inline(false),
                                ]),
                        ])
                        ->columnSpan(1),
                ]),
            ]);
    }

    /**
     * Connect to SSH host
     */
    public function connectToHost(): void
    {
        if (!$this->selectedHost) {
            $this->addError('selectedHost', 'Please select an SSH host.');
            return;
        }

        $this->isConnected = true;
        
        // The actual connection will be handled by the frontend JavaScript
        $this->dispatch('connect-xterm-terminal', [
            'hostId' => $this->selectedHost,
            'useBash' => $this->useBash,
            'showDebug' => $this->showDebug,
        ]);
    }

    /**
     * Execute command in terminal
     */
    public function executeCommand(): void
    {
        if (!$this->selectedHost) {
            $this->addError('selectedHost', 'Please select an SSH host.');
            return;
        }

        if (empty(trim($this->command))) {
            $this->addError('command', 'Please enter a command to execute.');
            return;
        }

        // Dispatch command execution to frontend
        $this->dispatch('execute-xterm-command', [
            'command' => $this->command,
            'hostId' => $this->selectedHost,
            'useBash' => $this->useBash,
        ]);
    }

    /**
     * Disconnect from SSH host
     */
    public function disconnect(): void
    {
        $this->isConnected = false;
        $this->sessionId = null;
        
        $this->dispatch('disconnect-xterm-terminal');
    }

    /**
     * Clear the terminal
     */
    public function clearTerminal(): void
    {
        $this->dispatch('clear-xterm-terminal');
    }

    /**
     * Get available SSH hosts for the form
     */
    public function getAvailableHosts(): array
    {
        return SshHost::where('active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Handle session status updates from frontend
     */
    public function updateSessionStatus(array $data): void
    {
        $this->sessionId = $data['sessionId'] ?? null;
        $this->isConnected = $data['connected'] ?? false;
    }

    /**
     * Get page header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('connect')
                ->label($this->isConnected ? 'Reconnect' : 'Connect')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action('connectToHost')
                ->visible(!$this->isConnected),
                
            \Filament\Actions\Action::make('execute')
                ->label('Run Command')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('executeCommand')
                ->visible($this->isConnected),
                
            \Filament\Actions\Action::make('clear')
                ->label('Clear')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action('clearTerminal')
                ->visible($this->isConnected),
                
            \Filament\Actions\Action::make('disconnect')
                ->label('Disconnect')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->action('disconnect')
                ->visible($this->isConnected),
        ];
    }
}