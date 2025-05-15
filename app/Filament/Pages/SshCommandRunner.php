<?php

namespace App\Filament\Pages;

use App\Models\SshConfig;
use App\Policies\SshCommandRunnerPolicy;
use App\Services\SshManagerService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Spatie\Ssh\Ssh;

class SshCommandRunner extends Page
{
    use InteractsWithForms;

    protected static string $policy = SshCommandRunnerPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'SSH Commands';

    protected static ?string $navigationGroup = 'SSH Management';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.ssh-command-runner';

    // Form properties
    public ?array $data = [
        'debug_mode' => false,
    ];

    // Results property
    public ?string $commandOutput = null;

    public ?string $commandError = null;

    public ?string $debugOutput = null;

    public bool $hasOutput = false;

    public bool $hasDebugOutput = false;

    public function mount(): void
    {
        // Ensure SSH directory is initialized
        $sshManager = new SshManagerService;
        try {
            $sshManager->initializeSshDirectory();
        } catch (\Exception $e) {
            // Just continue, we'll still try to load whatever connections are in the database
        }

        // Initialize form with default values, including debug_mode = false
        $this->form->fill([
            'debug_mode' => false,
            'ssh_config_id' => SshConfig::where('is_default', true)->first()?->id,
        ]);

        // Ensure data array has debug_mode initialized
        if (! isset($this->data['debug_mode'])) {
            $this->data['debug_mode'] = false;
        }
    }

    /**
     * Toggle the debug mode state
     */
    public function toggleDebugMode(): void
    {
        $currentState = $this->data['debug_mode'] ?? false;
        $this->data['debug_mode'] = ! $currentState;

        // Reset debug output if turning off debug mode
        if (! $this->data['debug_mode']) {
            $this->debugOutput = null;
            $this->hasDebugOutput = false;
        } else {
            // If turning on debug mode, show initial message
            $this->debugOutput = 'Debug mode enabled. Run a command to see detailed information.';
            $this->hasDebugOutput = true;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Use a grid with custom column spans
                Section::make()
                    ->schema([
                        // First column: Command textarea (50% width) on the left
                        Textarea::make('command')
                            ->label('Enter SSH Command(s)')
                            ->placeholder('Enter the SSH command(s) to execute...')
                            ->required()
                            ->rows(3)
                            ->columnSpan([
                                'xl' => 6,
                                'lg' => 6,
                                'md' => 6,
                                'sm' => 12, // Full width on small screens
                            ])
                            ->autosize(true),

                        // Right column container for SSH Connection and Debug toggle
                        \Filament\Forms\Components\Grid::make(1)
                            ->schema([
                                // SSH Connection dropdown
                                Select::make('ssh_config_id')
                                    ->label('SSH Connection')
                                    ->options(SshConfig::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->default(function () {
                                        return SshConfig::where('is_default', true)->first()?->id;
                                    }),

                                // We're using a manual toggle in the view, so this is no longer needed
                                \Filament\Forms\Components\Grid::make()
                                    ->schema([
                                        // Empty grid for spacing
                                    ])
                                    ->columns(1),
                            ])
                            ->columnSpan([
                                'xl' => 6,
                                'lg' => 6,
                                'md' => 6,
                                'sm' => 12, // Full width on small screens
                            ]),
                    ])
                    ->columns(12) // Using 12 columns for more flexible responsive layout
                    ->collapsible(false)
                    ->compact()
                    ->hiddenLabel(),
            ])
            ->statePath('data');
    }

    public function runCommand(): void
    {
        // Save the debug state before getting form data
        $debugWasEnabled = $this->data['debug_mode'] ?? false;

        $data = $this->form->getState();

        // Get SSH configuration
        $sshConfig = SshConfig::find($data['ssh_config_id']);

        if (! $sshConfig) {
            Notification::make()->title('SSH connection not found')->danger()->send();

            return;
        }

        try {
            // Create SSH connection
            $ssh = $this->createSshConnection($sshConfig);

            // Process command to handle bash -ci pattern if necessary
            $command = $data['command'];

            // Check if this is an 'sx' function call or similar format (sx host command)
            if (preg_match('/^sx\s+(\S+)\s+(.+)$/i', $command, $matches)) {
                // For sx function calls, execute the command part directly with bash -c
                $actualCommand = $matches[2];
                $process = $ssh->execute(
                    "bash -ci '".str_replace("'", "'\\''", $actualCommand)."'",
                );
            }
            // Check if the command uses bash -ci pattern (frequently used in shell scripts)
            elseif (
                preg_match('/bash\s+-ci\s+[\'"](.+?)[\'"]/', $command, $matches) ||
                preg_match(
                    '/ssh\s+\S+\s+(?:-\w+\s+)*(?:-t\s+)?[\'"]?bash\s+-ci\s+[\'"](.+?)[\'"]/',
                    $command,
                    $matches,
                )
            ) {
                // Extract the actual command and run it with bash -c
                $actualCommand = $matches[1];
                $process = $ssh->execute(
                    "bash -ci '".str_replace("'", "'\\''", $actualCommand)."'",
                );
            }
            // Check if the command contains a reference to $_HOST which needs to be replaced
            elseif (strpos($command, '$_HOST') !== false || strpos($command, 'ssh ') === 0) {
                // Replace $_HOST with the actual host from the config if it exists
                $processed = str_replace('$_HOST', $sshConfig->host, $command);
                // We don't need to ssh again since we're already doing that
                $processed = preg_replace('/^ssh\s+\S+\s+(?:-\w+\s+)*/', '', $processed);

                // If the remaining command has -t "bash -ci '...'", extract the actual command
                if (
                    preg_match(
                        '/-t\s+[\'"]bash\s+-ci\s+[\'"](.+?)[\'"][\'"]/',
                        $processed,
                        $matches,
                    )
                ) {
                    $processed = $matches[1];
                    $process = $ssh->execute(
                        "bash -ci '".str_replace("'", "'\\''", $processed)."'",
                    );
                } else {
                    // Execute the processed command
                    $process = $ssh->execute($processed);
                }
            }
            // For regular commands, execute normally
            else {
                $process = $ssh->execute($command);
            }

            // Store output
            $this->commandOutput = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            // Get debug mode state from the form data
            $debugMode = isset($data['debug_mode']) && filter_var($data['debug_mode'], FILTER_VALIDATE_BOOLEAN);

            // If debug mode is enabled (either before or now), show debug output
            if ($debugWasEnabled || $debugMode) {
                // Build debug output
                $debugOutput = "==== SSH CONNECTION ====\n";
                $debugOutput .= 'Host: '.$sshConfig->host."\n";
                $debugOutput .= 'Username: '.$sshConfig->username."\n";
                $debugOutput .= 'Port: '.$sshConfig->port."\n";

                $debugOutput .= "\n==== COMMAND EXECUTION ====\n";
                $debugOutput .= 'Command: '.$command;

                // Add process details
                $debugOutput .= "\n\nProcess Information:";
                $debugOutput .= "\n- Exit Code: ".$process->getExitCode();

                // Include raw error output in debug
                if (! empty($errorOutput)) {
                    $debugOutput .= "\n\nRaw Error Output:\n".$errorOutput;
                }

                // Store executed SSH command string (if available)
                if (method_exists($ssh, 'getExecuteCommand')) {
                    $debugOutput .= "\n\nExecuted command: ".$ssh->getExecuteCommand($command);
                }

                // Always set the debug output and flag on if it was previously enabled
                $this->debugOutput = $debugOutput;
                $this->hasDebugOutput = true;
            } else {
                // If debug mode is off (both before and now), reset debug output and flag
                $this->debugOutput = null;
                $this->hasDebugOutput = false;
            }

            // Filter out known hosts messages and other bash/ioctl noise from both outputs
            $this->commandOutput = $this->filterKnownHostsMessages($this->commandOutput);
            $errorOutput = $this->filterKnownHostsMessages($errorOutput);

            $this->commandError = $errorOutput;
            $this->hasOutput = ! empty($this->commandOutput) || ! empty($this->commandError);

            // Consider command successful if we have output, even if exit code is non-zero
            // This is more user-friendly since many SSH commands can return non-zero but still produce useful output
            if ($this->commandOutput || ! $this->commandError) {
                Notification::make()->title('Command executed successfully')->success()->send();
            } else {
                Notification::make()
                    ->title('Command execution failed')
                    ->danger()
                    ->body('The command returned an error. Check the output for details.')
                    ->send();
            }
        } catch (\Exception $e) {
            // Handle exceptions and preserve debug info
            $this->commandError = $e->getMessage();
            $this->hasOutput = true;

            // Always show debug information when there's an error if debug mode is on
            if ($debugWasEnabled) {
                $this->debugOutput = 'ERROR ENCOUNTERED: '.$e->getMessage();
                $this->hasDebugOutput = true;
            }

            Notification::make()
                ->title('SSH connection failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Create an SSH connection instance based on configuration
     */
    private function createSshConnection(SshConfig $sshConfig): Ssh
    {
        $ssh = Ssh::create($sshConfig->username, $sshConfig->host, $sshConfig->port);
        $debugInfo = [];

        // Add debug information
        $debugInfo[] = 'SSH Connection Details:';
        $debugInfo[] = "- Host: {$sshConfig->host}";
        $debugInfo[] = "- Port: {$sshConfig->port}";
        $debugInfo[] = "- Username: {$sshConfig->username}";

        // Use private key if available, otherwise use password
        if (! empty($sshConfig->private_key_path)) {
            $ssh->usePrivateKey($sshConfig->private_key_path);
            $debugInfo[] = '- Authentication: Private Key';
            $debugInfo[] = "- Key Path: {$sshConfig->private_key_path}";
        } elseif (! empty($sshConfig->password)) {
            $ssh->usePassword($sshConfig->password);
            $debugInfo[] = '- Authentication: Password';
        } else {
            $debugInfo[] = '- Authentication: None specified (using default system SSH keys)';
        }

        $debugInfo[] = "\nSSH Options:";

        // Disable strict host key checking and set additional options to suppress known hosts warnings
        $ssh->disableStrictHostKeyChecking();
        $debugInfo[] = '- StrictHostKeyChecking=no';

        // Set log level based on debug mode
        $data = $this->form->getState();
        $debugMode = $data['debug_mode'] ?? false;
        $debugMode = filter_var($debugMode, FILTER_VALIDATE_BOOLEAN);

        if ($debugMode) {
            $ssh->addExtraOption('-o LogLevel=DEBUG3');
            $ssh->addExtraOption('-v'); // Verbose mode
            $debugInfo[] = '- LogLevel=DEBUG3';
            $debugInfo[] = '- Verbose Mode: Enabled';
        } else {
            $ssh->addExtraOption('-o LogLevel=ERROR');
            $debugInfo[] = '- LogLevel=ERROR';
        }

        $ssh->addExtraOption('-o UserKnownHostsFile=/dev/null');
        $debugInfo[] = '- UserKnownHostsFile=/dev/null';

        // Specific options to avoid terminal control issues
        $ssh->addExtraOption('-o RequestTTY=no'); // More reliable than -T in some cases
        $ssh->addExtraOption('-o BatchMode=yes'); // Avoid password prompts if keys aren't set up
        $debugInfo[] = '- RequestTTY=no';
        $debugInfo[] = '- BatchMode=yes';

        // We'll let the toggleDebugMode and runCommand methods handle the debug output
        // We're not modifying debug output here to avoid duplication

        return $ssh;
    }

    /**
     * Filter out known hosts warning messages from SSH output
     */
    private function filterKnownHostsMessages(string $output): string
    {
        // List of patterns to filter out
        $patterns = [
            '/Warning: Permanently added .* to the list of known hosts.\s*/i',
            '/The authenticity of host .* can\'t be established.\s*/i',
            '/Are you sure you want to continue connecting \(yes\/no\)?\s*/i',
            '/bash: cannot set terminal process group.*: Inappropriate ioctl for device\s*/i',
            '/bash: no job control in this shell\s*/i',
        ];

        // Apply each pattern
        foreach ($patterns as $pattern) {
            $output = preg_replace($pattern, '', $output);
        }

        return trim($output);
    }
}
