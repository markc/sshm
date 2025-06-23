<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use App\Settings\SshSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class SshCommandRunner extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'SSH Commands';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.ssh-command-runner';

    public ?string $currentProcessId = null;

    public bool $isCommandRunning = false;

    public bool $useBashMode = false;

    public bool $showDebug = false;

    public bool $hasTerminalOutput = false;

    public function mount(): void
    {
        // Initialize properties
        $this->isCommandRunning = false;
        $this->currentProcessId = null;

        // Set default SSH host from settings
        $settings = app(SshSettings::class);
        $defaultHost = $settings->getDefaultSshHost();

        if ($defaultHost) {
            // Find the host by name and set as selected
            $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
            if ($host) {
                $this->selectedHost = (string) $host->id;
            }
        }
    }

    public ?string $selectedHost = null;

    public ?string $command = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // Left side - Command textarea (3 rows, no label)
                        Textarea::make('command')
                            ->hiddenLabel()
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter SSH command(s) to execute...')
                            ->extraAttributes([
                                'style' => 'resize: none;',
                                'id' => 'command-input',
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
                                            ->icon(fn () => $this->isCommandRunning ? 'heroicon-o-arrow-path' : 'heroicon-o-play')
                                            ->iconPosition('before')
                                            ->color(fn () => $this->isCommandRunning ? 'danger' : 'primary')
                                            ->size('lg')
                                            ->extraAttributes(fn () => [
                                                'id' => 'command-btn',
                                                'class' => $this->isCommandRunning ? 'w-full' : 'w-full',
                                            ])
                                            ->action(fn () => $this->isCommandRunning ? $this->stopTerminalCommand() : $this->startTerminalCommand())
                                            ->requiresConfirmation(false)
                                            ->button(),
                                    ]),

                                    // Debug Toggle
                                    Toggle::make('showDebug')
                                        ->label('Show Debug Information')
                                        ->inline(true)
                                        ->live()
                                        ->extraAttributes(['class' => 'mt-4']),
                                ])->columnSpan(1),

                                // Right sub-column: SSH Host selector and Bash Mode toggle
                                Group::make([
                                    // SSH Host selector (no label, custom placeholder)
                                    Select::make('selectedHost')
                                        ->hiddenLabel()
                                        ->placeholder('Select SSH Host')
                                        ->options(function () {
                                            return SshHost::where('active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->default(function () {
                                            $settings = app(SshSettings::class);
                                            $defaultHost = $settings->getDefaultSshHost();

                                            if ($defaultHost) {
                                                $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
                                                if ($host) {
                                                    return (string) $host->id;
                                                }
                                            }

                                        })
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->selectedHost = $state;
                                            }
                                        }),

                                    // Bash Mode Toggle
                                    Toggle::make('useBashMode')
                                        ->label('Use Bash Mode')
                                        ->inline(true)
                                        ->extraAttributes(['class' => 'mt-4']),
                                ])->columnSpan(1),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    protected function getFormModel(): string
    {
        return SshCommandRunner::class;
    }

    protected function getViewData(): array
    {
        return [
            'showDebug' => $this->showDebug,
            'hasTerminalOutput' => $this->hasTerminalOutput,
        ];
    }

    public function startTerminalCommand(): void
    {
        $this->validate([
            'command' => 'required|string',
            'selectedHost' => 'required',
        ]);

        $this->isCommandRunning = true;
        $this->hasTerminalOutput = true;

        // Generate process ID and start SSH command directly via Livewire
        $processId = (string) \Illuminate\Support\Str::uuid();
        $this->currentProcessId = $processId;

        // Store authorization info for channel access
        \Illuminate\Support\Facades\Cache::put("process:{$processId}:user", auth()->id(), now()->addHours(2));
        \Illuminate\Support\Facades\Cache::put("process:{$processId}:host", $this->selectedHost, now()->addHours(2));

        // Dispatch the job to the queue for execution with bash mode flag
        \App\Jobs\RunSshCommand::dispatch($this->command, $processId, auth()->id(), (int) $this->selectedHost, $this->useBashMode);

        // Notify frontend to subscribe to WebSocket channel
        $this->dispatch('subscribe-to-process', [
            'process_id' => $processId,
        ]);
    }

    public function stopTerminalCommand(): void
    {
        if ($this->currentProcessId) {
            // Get PID and kill the process
            $pid = \Illuminate\Support\Facades\Cache::get("process:{$this->currentProcessId}:pid");

            if ($pid) {
                try {
                    \Illuminate\Support\Facades\Process::run("kill {$pid}");
                    \App\Events\SshOutputReceived::dispatch($this->currentProcessId, 'status', 'Process terminated by user.');

                    // Clean up cache keys
                    \Illuminate\Support\Facades\Cache::forget("process:{$this->currentProcessId}:user");
                    \Illuminate\Support\Facades\Cache::forget("process:{$this->currentProcessId}:host");
                    \Illuminate\Support\Facades\Cache::forget("process:{$this->currentProcessId}:pid");

                } catch (\Exception $e) {
                    \App\Events\SshOutputReceived::dispatch($this->currentProcessId, 'err', 'Failed to terminate process: ' . $e->getMessage());
                }
            }

            $this->isCommandRunning = false;
            $this->currentProcessId = null;
        }
    }

    public function setProcessId(string $processId): void
    {
        $this->currentProcessId = $processId;
    }

    public function setRunningState(bool $isRunning): void
    {
        $this->isCommandRunning = $isRunning;

        if (! $isRunning) {
            $this->currentProcessId = null;
        }
    }

    protected function getListeners(): array
    {
        return [
            'setProcessId' => 'setProcessId',
            'setRunningState' => 'setRunningState',
        ];
    }
}
