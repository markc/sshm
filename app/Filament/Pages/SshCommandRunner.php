<?php

namespace App\Filament\Pages;

use App\Models\SshConfig;
use App\Policies\SshCommandRunnerPolicy;
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

    protected static ?string $navigationLabel = 'SSH Command Runner';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.ssh-command-runner';

    // Form properties
    public ?array $data = [];

    // Results property
    public ?string $commandOutput = null;

    public ?string $commandError = null;

    public bool $hasOutput = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Use a grid with custom column spans
                Section::make()
                    ->schema([
                        // First column: Command textarea (75% width) on the left
                        Textarea::make('command')
                            ->label('Enter SSH Command(s)')
                            ->placeholder('Enter the SSH command(s) to execute...')
                            ->required()
                            ->rows(3)
                            ->columnSpan(3)
                            ->autosize(true),

                        // Second column: SSH Connection dropdown (25% width) on the right
                        Select::make('ssh_config_id')
                            ->label('SSH Connection')
                            ->options(SshConfig::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->columnSpan(1)
                            ->default(function () {
                                return SshConfig::where('is_default', true)->first()?->id;
                            }),
                    ])
                    ->columns(4) // Using 4 columns to get the 25%/75% split
                    ->collapsible(false)
                    ->compact()
                    ->hiddenLabel(),
            ])
            ->statePath('data');
    }

    public function runCommand(): void
    {
        $data = $this->form->getState();

        // Get SSH configuration
        $sshConfig = SshConfig::find($data['ssh_config_id']);

        if (! $sshConfig) {
            Notification::make()
                ->title('SSH connection not found')
                ->danger()
                ->send();

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
                $process = $ssh->execute("bash -ci '" . str_replace("'", "'\\''", $actualCommand) . "'");
            }
            // Check if the command uses bash -ci pattern (frequently used in shell scripts)
            elseif (preg_match('/bash\s+-ci\s+[\'"](.+?)[\'"]/', $command, $matches) ||
                   preg_match('/ssh\s+\S+\s+(?:-\w+\s+)*(?:-t\s+)?[\'"]?bash\s+-ci\s+[\'"](.+?)[\'"]/', $command, $matches)) {
                // Extract the actual command and run it with bash -c
                $actualCommand = $matches[1];
                $process = $ssh->execute("bash -ci '" . str_replace("'", "'\\''", $actualCommand) . "'");
            }
            // Check if the command contains a reference to $_HOST which needs to be replaced
            elseif (strpos($command, '$_HOST') !== false || strpos($command, 'ssh ') === 0) {
                // Replace $_HOST with the actual host from the config if it exists
                $processed = str_replace('$_HOST', $sshConfig->host, $command);
                // We don't need to ssh again since we're already doing that
                $processed = preg_replace('/^ssh\s+\S+\s+(?:-\w+\s+)*/', '', $processed);

                // If the remaining command has -t "bash -ci '...'", extract the actual command
                if (preg_match('/-t\s+[\'"]bash\s+-ci\s+[\'"](.+?)[\'"][\'"]/', $processed, $matches)) {
                    $processed = $matches[1];
                    $process = $ssh->execute("bash -ci '" . str_replace("'", "'\\''", $processed) . "'");
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

            // Filter out known hosts messages and other bash/ioctl noise from both outputs
            $this->commandOutput = $this->filterKnownHostsMessages($this->commandOutput);
            $errorOutput = $process->getErrorOutput();
            $errorOutput = $this->filterKnownHostsMessages($errorOutput);

            $this->commandError = $errorOutput;
            $this->hasOutput = !empty($this->commandOutput) || !empty($this->commandError);

            // Consider command successful if we have output, even if exit code is non-zero
            // This is more user-friendly since many SSH commands can return non-zero but still produce useful output
            if ($this->commandOutput || !$this->commandError) {
                Notification::make()
                    ->title('Command executed successfully')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Command execution failed')
                    ->danger()
                    ->body('The command returned an error. Check the output for details.')
                    ->send();
            }
        } catch (\Exception $e) {
            $this->commandError = $e->getMessage();
            $this->hasOutput = true;

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

        // Use private key if available, otherwise use password
        if (! empty($sshConfig->private_key_path)) {
            $ssh->usePrivateKey($sshConfig->private_key_path);
        } elseif (! empty($sshConfig->password)) {
            $ssh->usePassword($sshConfig->password);
        }

        // Disable strict host key checking and set additional options to suppress known hosts warnings
        // In production, this should be configurable
        $ssh->disableStrictHostKeyChecking();
        $ssh->addExtraOption('-o LogLevel=ERROR');
        $ssh->addExtraOption('-o UserKnownHostsFile=/dev/null');

        // Specific options to avoid terminal control issues
        $ssh->addExtraOption('-o RequestTTY=no'); // More reliable than -T in some cases
        $ssh->addExtraOption('-o BatchMode=yes'); // Avoid password prompts if keys aren't set up

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