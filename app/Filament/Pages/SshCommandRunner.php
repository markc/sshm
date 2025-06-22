<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use App\Services\SshService;
use App\Settings\SshSettings;
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
use Filament\Schemas\Components\Section;
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

    public ?string $selectedHost = null;

    public ?string $command = null;

    public function mount(): void
    {
        $this->commandOutput = null;
        $this->streamingOutput = '';
        $this->isCommandRunning = false;
        $this->debugOutput = '';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Form section with proper spacing
                Section::make()
                    ->columns(2)
                    ->schema([
                        // Left column - Command textarea
                        Textarea::make('command')
                            ->hiddenLabel()
                            ->required()
                            ->rows(4)
                            ->placeholder('Enter SSH command(s) to execute...')
                            ->extraAttributes(['style' => 'resize: none;'])
                            ->columnSpan(1),

                        // Right column - Controls
                        Group::make([
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

                            Grid::make(3)
                                ->schema([
                                    Group::make([
                                        Actions::make([
                                            Action::make('runCommand')
                                                ->label(fn () => $this->isCommandRunning ? 'Running...' : 'Run Command')
                                                ->disabled(fn () => $this->isCommandRunning)
                                                ->icon(fn () => $this->isCommandRunning ? 'heroicon-o-arrow-path' : 'heroicon-o-play')
                                                ->color('primary')
                                                ->size('lg')
                                                ->extraAttributes(fn () => $this->isCommandRunning ? ['class' => 'animate-pulse'] : [])
                                                ->action(fn () => $this->runCommand())
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full']),
                                        ]),
                                    ])->columnSpan(1)
                                        ->extraAttributes(['class' => 'flex items-center']),

                                    Toggle::make('verboseDebug')
                                        ->label('Debug')
                                        ->inline(true)
                                        ->columnSpan(1)
                                        ->extraAttributes(['class' => 'flex items-center justify-center']),

                                    Toggle::make('useBash')
                                        ->label('Use bash')
                                        ->inline(true)
                                        ->columnSpan(1)
                                        ->extraAttributes(['class' => 'flex items-center justify-center']),
                                ]),
                        ])->columnSpan(1),
                    ]),

            ]);
    }

    public function runCommand(): void
    {
        $this->validate([
            'command' => 'required|string',
            'selectedHost' => 'required',
        ]);

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

            if (! empty($result['output'])) {
                $this->streamingOutput = $result['output'];
            }

            if ($result['success']) {
                Notification::make()
                    ->success()
                    ->title('Command Completed (Exit Code: ' . $result['exit_code'] . ')')
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title('Command Failed (Exit Code: ' . $result['exit_code'] . ')')
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

            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }
}
