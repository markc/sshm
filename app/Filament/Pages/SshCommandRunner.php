<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use App\Services\SshService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class SshCommandRunner extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'SSH Commands';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.ssh-command-runner';

    public ?array $commandOutput = null;

    public string $streamingOutput = '';

    public bool $isCommandRunning = false;

    public bool $verboseDebug = false;

    public string $debugOutput = '';

    public bool $useBash = false;

    public function mount(): void
    {
        // Initialize properties to ensure they're properly tracked by Livewire
        $this->commandOutput = null;
        $this->streamingOutput = '';
        $this->isCommandRunning = false;
        $this->debugOutput = '';
    }

    protected ?Ssh $currentSshProcess = null;

    public ?string $selectedHost = null;

    public ?string $command = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Command input and host selection at the top
                Grid::make(2)
                    ->schema([
                        // Command textarea - left side
                        Textarea::make('command')
                            ->label('Enter SSH Command(s)')
                            ->required()
                            ->rows(5)
                            ->placeholder('Enter SSH command(s) to execute...')
                            ->extraAttributes(['style' => 'resize: none;'])
                            ->columnSpan(1),

                        // SSH Host selector - right side
                        Select::make('selectedHost')
                            ->label('Select SSH Host')
                            ->options(function () {
                                return SshHost::where('active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->selectedHost = $state;
                                }
                            })
                            ->columnSpan(1),
                    ]),

                // Run command button and toggle controls in a single horizontal row
                Grid::make(3)
                    ->schema([
                        // Run Command button
                        Group::make([
                            Actions::make([
                                Action::make('runCommand')
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
                                    ->extraAttributes(['class' => 'w-full']),
                            ]),

                            Actions::make([
                                Action::make('cancelCommand')
                                    ->label('Cancel Command')
                                    ->visible(fn () => $this->isCommandRunning)
                                    ->icon('heroicon-o-x-circle')
                                    ->iconPosition('before')
                                    ->color('danger')
                                    ->size('lg')
                                    ->action(function () {
                                        $this->cancelCommand();
                                    })
                                    ->requiresConfirmation(false)
                                    ->button()
                                    ->extraAttributes(['class' => 'w-full']),
                            ])->visible(fn () => $this->isCommandRunning),
                        ])
                            ->columnSpan(1),

                        // Verbose Debug toggle
                        Toggle::make('verboseDebug')
                            ->label('Verbose Debug')
                            ->inline(true)
                            ->columnSpan(1),

                        // Use bash toggle
                        Toggle::make('useBash')
                            ->label('Use bash')
                            ->inline(true)
                            ->columnSpan(1),
                    ]),

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
            'selectedHost' => 'required',
        ]);

        // Reset output and set running state
        $this->streamingOutput = '';
        $this->commandOutput = null;
        $this->debugOutput = '';
        $this->isCommandRunning = true;

        $sshService = app(SshService::class);

        try {
            $host = SshHost::findOrFail($this->selectedHost);
            $result = $sshService->executeCommandWithStreaming(
                $host,
                $this->command,
                function ($type, $line) {
                    if ($type === Process::OUT || $type === 'out') {
                        $this->streamingOutput .= $line;
                        $this->dispatch('outputUpdated', [$this->streamingOutput]);
                    }
                },
                $this->verboseDebug,
                function ($debugLine) {
                    $this->debugOutput .= $debugLine . "\n";
                    $this->dispatch('debugUpdated', [$this->debugOutput]);
                },
                $this->useBash
            );

            $this->isCommandRunning = false;
            $this->commandOutput = $result;

            // Always update streaming output with final result to ensure UI displays something
            if (! empty($result['output'])) {
                $this->streamingOutput = $result['output'];
                $this->dispatch('outputUpdated', [$this->streamingOutput]);
            }

            // Debug: Log what we're getting
            if ($this->verboseDebug && ! empty($this->debugOutput)) {
                $this->debugOutput .= "\n=== Final State ===\n";
                $this->debugOutput .= 'Streaming Output Length: ' . strlen($this->streamingOutput) . "\n";
                $this->debugOutput .= 'Result Output Length: ' . strlen($result['output']) . "\n";
                $this->debugOutput .= 'Command Output Set: ' . ($this->commandOutput ? 'Yes' : 'No') . "\n";
                $this->dispatch('debugUpdated', [$this->debugOutput]);
            }

            // Force Livewire to refresh the UI
            $this->dispatch('$refresh');

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
        } catch (Exception $e) {
            $this->isCommandRunning = false;
            $this->commandOutput = [
                'success' => false,
                'output' => $this->streamingOutput,
                'error' => $e->getMessage(),
                'exit_code' => -1,
            ];

            // Force Livewire to refresh the UI
            $this->dispatch('$refresh');

            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function cancelCommand(): void
    {
        $this->isCommandRunning = false;
        $this->streamingOutput .= "\n\n--- Command cancelled by user ---\n";
        $this->dispatch('outputUpdated', [$this->streamingOutput]);

        $this->commandOutput = [
            'success' => false,
            'output' => $this->streamingOutput,
            'error' => 'Command cancelled by user',
            'exit_code' => -1,
        ];

        // Force Livewire to update the UI
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Command Cancelled')
            ->body('The SSH command was cancelled')
            ->warning()
            ->send();
    }
}
