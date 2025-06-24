<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
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
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-window';

    protected static ?string $navigationLabel = 'SSH Terminal';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.xterm-ssh-terminal';

    // Form state
    public ?int $selectedHost = null;

    public string $command = '';

    public bool $useBash = false;

    public bool $showDebug = false;

    // Terminal state
    public bool $isConnected = false;

    public bool $isCommandRunning = false;

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
        // Set default SSH host from settings
        $settings = app(\App\Settings\SshSettings::class);
        $defaultHost = $settings->getDefaultSshHost();

        if ($defaultHost) {
            // Find the host by name and set as selected
            $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
            if ($host) {
                $this->selectedHost = $host->id;
            }
        }

        // Fallback: Set default host if only one exists and no default configured
        if (! $this->selectedHost) {
            $hosts = SshHost::where('active', true)->get();
            if ($hosts->count() === 1) {
                $this->selectedHost = $hosts->first()->id;
            }
        }

        // Set default command from settings
        $this->command = $settings->getDefaultCommand() ?? 'ls -al';
    }

    /**
     * Configure the form schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // Left side - Command textarea
                        \Filament\Forms\Components\Textarea::make('command')
                            ->hiddenLabel()
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter SSH command(s) to execute...')
                            ->extraAttributes([
                                'style' => 'resize: none;',
                                'id' => 'xterm-command-input',
                            ])
                            ->columnSpan(1),

                        // Right side - Two sub-columns for controls
                        Grid::make(2)
                            ->schema([
                                // Left sub-column: Run button and Debug toggle
                                Group::make([
                                    // Single button with dual states: Run/Stop
                                    Actions::make([
                                        Action::make('commandButton')
                                            ->label(fn () => $this->isCommandRunning ? 'Stop Command' : 'Run Command')
                                            ->icon(fn () => $this->isCommandRunning ? 'heroicon-o-stop' : 'heroicon-o-play')
                                            ->iconPosition('before')
                                            ->color(fn () => $this->isCommandRunning ? 'danger' : 'primary')
                                            ->size('lg')
                                            ->extraAttributes(fn () => [
                                                'id' => 'xterm-command-btn',
                                                'class' => 'w-full',
                                            ])
                                            ->action(fn () => $this->isCommandRunning ? $this->stopCommand() : $this->runCommand())
                                            ->requiresConfirmation(false)
                                            ->button(),
                                    ]),

                                    // Debug Toggle
                                    \Filament\Forms\Components\Toggle::make('showDebug')
                                        ->label('Show Debug')
                                        ->inline(true)
                                        ->live(),
                                ])->columnSpan(1),

                                // Right sub-column: SSH Host selector and Bash Mode toggle
                                Group::make([
                                    // SSH Host selector
                                    \Filament\Forms\Components\Select::make('selectedHost')
                                        ->hiddenLabel()
                                        ->placeholder('Select SSH Host')
                                        ->options(function () {
                                            return SshHost::where('active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->default(function () {
                                            $settings = app(\App\Settings\SshSettings::class);
                                            $defaultHost = $settings->getDefaultSshHost();

                                            if ($defaultHost) {
                                                $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
                                                if ($host) {
                                                    return $host->id;
                                                }
                                            }

                                            // Fallback: Set default host if only one exists
                                            $hosts = SshHost::where('active', true)->get();
                                            if ($hosts->count() === 1) {
                                                return $hosts->first()->id;
                                            }

                                        })
                                        ->required()
                                        ->live()
                                        ->searchable(),

                                    // Bash Mode toggle
                                    \Filament\Forms\Components\Toggle::make('useBash')
                                        ->label('Use Bash')
                                        ->inline(true)
                                        ->live(),
                                ])->columnSpan(1),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    /**
     * Run SSH command (primary action from form button)
     */
    public function runCommand(): void
    {
        if (! $this->selectedHost) {
            $this->addError('selectedHost', 'Please select an SSH host.');

            return;
        }

        if (empty(trim($this->command))) {
            $this->addError('command', 'Please enter a command to execute.');

            return;
        }

        $this->isCommandRunning = true;

        // Connect to terminal if not already connected, then execute command
        if (! $this->isConnected) {
            $this->dispatch('connect-and-execute-xterm-command', [
                'hostId' => $this->selectedHost,
                'useBash' => $this->useBash,
                'showDebug' => $this->showDebug,
                'command' => $this->command,
            ]);
        } else {
            // Already connected, just execute command
            $this->dispatch('execute-xterm-command', [
                'command' => $this->command,
                'hostId' => $this->selectedHost,
                'useBash' => $this->useBash,
            ]);
        }
    }

    /**
     * Stop running command
     */
    public function stopCommand(): void
    {
        $this->isCommandRunning = false;

        // Dispatch stop command to frontend
        $this->dispatch('stop-xterm-command');
    }

    /**
     * Connect to SSH host
     */
    public function connectToHost(): void
    {
        if (! $this->selectedHost) {
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
     * Set the running state (called from frontend via Livewire event)
     */
    public function setRunningState(bool $isRunning): void
    {
        $this->isCommandRunning = $isRunning;

        if (! $isRunning) {
            $this->sessionId = null;
        }
    }

    /**
     * Handle command completion (called from frontend) - DEPRECATED
     * Use setRunningState instead
     */
    public function onCommandComplete(): void
    {
        $this->setRunningState(false);
    }

    /**
     * Get Livewire listeners for this component
     */
    protected function getListeners(): array
    {
        return [
            'setRunningState' => 'setRunningState',
        ];
    }

    /**
     * Get page header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label($this->isConnected ? 'Reconnect' : 'Connect')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action('connectToHost')
                ->visible(! $this->isConnected),

            Action::make('execute')
                ->label('Run Command')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('executeCommand')
                ->visible($this->isConnected),

            Action::make('clear')
                ->label('Clear')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action('clearTerminal')
                ->visible($this->isConnected),

            Action::make('disconnect')
                ->label('Disconnect')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->action('disconnect')
                ->visible($this->isConnected),
        ];
    }
}
