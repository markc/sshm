<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use App\Services\SshService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
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

    public ?string $hostname = null;

    public ?string $port = '22';

    public ?string $username = 'root';

    public ?string $identityFile = null;

    public bool $useCustomConnection = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // Left side - 50% width for command textarea
                        Group::make([
                            Textarea::make('command')
                                ->label('Enter SSH Command(s)')
                                ->required()
                                ->rows(8)
                                ->placeholder('Enter SSH command(s) to execute...')
                                ->extraAttributes(['style' => 'resize: none;']),
                        ])
                            ->columnSpan(1),

                        // Right side - 50% width for controls
                        Group::make([
                            // SSH Host selector and Run button on same line
                            Grid::make(2)
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
                                        ])->extraAttributes(['class' => 'mt-6']),

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
                                        ])->visible(fn () => $this->isCommandRunning)
                                            ->extraAttributes(['class' => 'mt-2']),
                                    ])
                                        ->columnSpan(1),
                                ]),

                            // Custom connection fields (stacked vertically in the right column)
                            TextInput::make('hostname')
                                ->label('Hostname')
                                ->required()
                                ->hidden(fn () => ! $this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),

                            TextInput::make('port')
                                ->label('Port')
                                ->numeric()
                                ->default('22')
                                ->hidden(fn () => ! $this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),

                            TextInput::make('username')
                                ->label('Username')
                                ->default('root')
                                ->hidden(fn () => ! $this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-2']),

                            TextInput::make('identityFile')
                                ->label('Identity File (optional)')
                                ->placeholder('~/.ssh/id_ed25519')
                                ->hidden(fn () => ! $this->useCustomConnection)
                                ->extraAttributes(['class' => 'mb-4']),

                            Toggle::make('verboseDebug')
                                ->label('Verbose Debug')
                                ->inline(true),

                            Toggle::make('useBash')
                                ->label('Use bash')
                                ->inline(true),
                        ])
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
        try {
            $this->validate([
                'command' => 'required|string',
                'selectedHost' => $this->useCustomConnection ? 'nullable' : 'required',
                'hostname' => $this->useCustomConnection ? 'required|string' : 'nullable',
                'port' => $this->useCustomConnection ? 'required|numeric' : 'nullable',
                'username' => $this->useCustomConnection ? 'required|string' : 'nullable',
            ]);
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Validation Error')
                ->body($e->validator->errors()->first())
                ->danger()
                ->send();

            return;
        }

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
            } else {
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
            }

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

    public function toggleConnectionMode(): void
    {
        $this->useCustomConnection = ! $this->useCustomConnection;
        $this->selectedHost = null;
        $this->hostname = null;
        $this->port = '22';
        $this->username = 'root';
        $this->identityFile = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleConnection')
                ->label(fn () => $this->useCustomConnection ? 'Use Saved Host' : 'Use Custom Connection')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->toggleConnectionMode()),
        ];
    }
}
