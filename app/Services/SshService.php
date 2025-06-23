<?php

namespace App\Services;

use App\Models\SshHost;
use App\Models\SshKey;
use App\Settings\SshSettings;
use Exception;
use Illuminate\Support\Facades\Process;
use Spatie\Ssh\Ssh;

class SshService
{
    /**
     * Execute an SSH command on a remote server
     */
    public function executeCommand(SshHost $host, string $command): array
    {
        try {
            // Set up the key file if needed
            $privateKeyPath = null;
            if ($host->identity_file) {
                $sshKey = SshKey::where('name', $host->identity_file)->first();
                if ($sshKey) {
                    $privateKeyPath = $this->savePrivateKeyToTempFile($sshKey);
                }
            }

            // Create the SSH connection
            $ssh = Ssh::create($host->user, $host->hostname)
                ->setTimeout(app(SshSettings::class)->getTimeout());

            // Configure port if not default
            if ($host->port && $host->port != 22) {
                $ssh->usePort($host->port);
            }

            // Configure private key if available
            if ($privateKeyPath) {
                $ssh->usePrivateKey($privateKeyPath);
            }

            // Disable strict host key checking and reduce verbosity
            $ssh->disableStrictHostKeyChecking()
                ->enableQuietMode();

            // Execute the command with job control messages suppressed
            $wrappedCommand = "bash -c '{$command}' 2>&1 | grep -v 'cannot set terminal process group' | grep -v 'no job control in this shell'";
            $process = $ssh->execute($wrappedCommand);

            // Clean up the temporary key file
            if ($privateKeyPath && file_exists($privateKeyPath)) {
                unlink($privateKeyPath);
            }

            // Return the results
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => -1,
            ];
        }
    }

    /**
     * Execute an SSH command with streaming output
     */
    public function executeCommandWithStreaming(SshHost $host, string $command, ?callable $outputCallback = null, bool $verboseDebug = false, ?callable $debugCallback = null, bool $useBash = false): array
    {
        try {
            if ($debugCallback && $verboseDebug) {
                $debugCallback('=== SSH Debug Information ===');
                $debugCallback("Host: {$host->hostname}:{$host->port}");
                $debugCallback("User: {$host->user}");
                $debugCallback('Identity File: ' . ($host->identity_file ?: 'None'));
                $debugCallback("Command: {$command}");
                $debugCallback('Use Bash: ' . ($useBash ? 'Yes' : 'No'));
                $debugCallback('Timestamp: ' . now()->toDateTimeString());
                $debugCallback('=== Connection Setup ===');
            }

            // Set up the key file if needed
            $privateKeyPath = null;
            if ($host->identity_file) {
                $sshKey = SshKey::where('name', $host->identity_file)->first();
                if ($sshKey) {
                    $privateKeyPath = $this->savePrivateKeyToTempFile($sshKey);
                    if ($debugCallback && $verboseDebug) {
                        $debugCallback("Private key saved to temporary file: {$privateKeyPath}");
                    }
                }
            }

            // Create the SSH connection with streaming support
            $ssh = Ssh::create($host->user, $host->hostname)
                ->setTimeout(app(SshSettings::class)->getTimeout());

            if ($debugCallback && $verboseDebug) {
                $debugCallback('SSH connection object created');
            }

            // Configure port if not default
            if ($host->port && $host->port != 22) {
                $ssh->usePort($host->port);
                if ($debugCallback && $verboseDebug) {
                    $debugCallback("Port configured: {$host->port}");
                }
            }

            // Configure private key if available
            if ($privateKeyPath) {
                $ssh->usePrivateKey($privateKeyPath);
                if ($debugCallback && $verboseDebug) {
                    $debugCallback('Private key configured');
                }
            }

            // Configure SSH options based on debug mode
            if ($verboseDebug) {
                $ssh->disableStrictHostKeyChecking();
                if ($debugCallback) {
                    $debugCallback('SSH configured with verbose mode (no quiet mode)');
                }
            } else {
                $ssh->disableStrictHostKeyChecking()
                    ->enableQuietMode();
                if ($debugCallback) {
                    $debugCallback('SSH configured with quiet mode');
                }
            }

            // Set up output streaming if callback provided
            if ($outputCallback) {
                $ssh->onOutput($outputCallback);
                if ($debugCallback && $verboseDebug) {
                    $debugCallback('Output streaming callback configured');
                }
            }

            if ($debugCallback && $verboseDebug) {
                $debugCallback('=== Command Execution ===');
                $debugCallback('Starting command execution...');
            }

            // Prepare the command based on options
            $finalCommand = $command;

            if ($useBash) {
                // Wrap command in interactive bash
                $finalCommand = "bash -ci '{$command}'";
                if ($debugCallback && $verboseDebug) {
                    $debugCallback("Command wrapped in bash: {$finalCommand}");
                }
            }

            // Execute the command - use different wrapping based on debug mode
            if ($verboseDebug) {
                // In debug mode, show all output including SSH messages
                $process = $ssh->execute($finalCommand);
            } else {
                // In normal mode, filter out job control messages (but preserve bash wrapping if used)
                if ($useBash) {
                    // If using bash, apply filtering after bash execution but preserve exit code
                    $wrappedCommand = "({$finalCommand}) 2>&1 | grep -v 'cannot set terminal process group' | grep -v 'no job control in this shell'; exit \${PIPESTATUS[0]}";
                } else {
                    // Standard filtering for non-bash commands but preserve exit code
                    $wrappedCommand = "(bash -c '{$finalCommand}') 2>&1 | grep -v 'cannot set terminal process group' | grep -v 'no job control in this shell'; exit \${PIPESTATUS[0]}";
                }
                $process = $ssh->execute($wrappedCommand);
            }

            // Clean up the temporary key file
            if ($privateKeyPath && file_exists($privateKeyPath)) {
                unlink($privateKeyPath);
                if ($debugCallback && $verboseDebug) {
                    $debugCallback('Temporary private key file cleaned up');
                }
            }

            if ($debugCallback && $verboseDebug) {
                $debugCallback('=== Command Results ===');
                $debugCallback('Success: ' . ($process->isSuccessful() ? 'Yes' : 'No'));
                $debugCallback('Exit Code: ' . $process->getExitCode());
                $debugCallback('Output Length: ' . strlen($process->getOutput()) . ' characters');
                $debugCallback('Error Length: ' . strlen($process->getErrorOutput()) . ' characters');
                $debugCallback('=== Debug Complete ===');
            }

            // Return the results
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode(),
            ];
        } catch (Exception $e) {
            if ($debugCallback && $verboseDebug) {
                $debugCallback('=== EXCEPTION OCCURRED ===');
                $debugCallback('Error: ' . $e->getMessage());
                $debugCallback('File: ' . $e->getFile());
                $debugCallback('Line: ' . $e->getLine());
            }

            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => -1,
            ];
        }
    }

    /**
     * Initialize the SSH directory structure
     */
    public function initSshDirectory(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";
            $configPath = "{$sshPath}/config";
            $configDPath = "{$sshPath}/config.d";
            $authKeysPath = "{$sshPath}/authorized_keys";

            // Create .ssh directory if it doesn't exist
            if (! is_dir($sshPath)) {
                mkdir($sshPath, 0700);
            }

            // Create authorized_keys file if it doesn't exist
            if (! file_exists($authKeysPath)) {
                touch($authKeysPath);
                chmod($authKeysPath, 0600);
            }

            // Create config.d directory if it doesn't exist
            if (! is_dir($configDPath)) {
                mkdir($configDPath, 0700);
            }

            // Create or update config file
            if (! file_exists($configPath)) {
                $configContent = '# Created by SSHM on ' . date('Y-m-d') . PHP_EOL;
                $configContent .= 'Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-gcm@openssh.com,aes256-gcm@openssh.com,chacha20-poly1305@openssh.com' . PHP_EOL . PHP_EOL;
                $configContent .= 'Include ~/.ssh/config.d/*' . PHP_EOL . PHP_EOL;
                $configContent .= 'Host *' . PHP_EOL;
                $configContent .= '  TCPKeepAlive yes' . PHP_EOL;
                $configContent .= '  ServerAliveInterval 30' . PHP_EOL;
                $configContent .= '  ForwardAgent yes' . PHP_EOL;
                $configContent .= '  AddKeysToAgent yes' . PHP_EOL;
                $configContent .= '  IdentitiesOnly yes' . PHP_EOL;

                file_put_contents($configPath, $configContent);
                chmod($configPath, 0600);
            }

            $this->updatePermissions();

            return [
                'success' => true,
                'message' => 'SSH directory structure initialized successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to initialize SSH directory: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update SSH directory permissions
     */
    public function updatePermissions(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";

            // Find all directories and set permissions to 700
            $process = Process::run("find {$sshPath} -type d -exec chmod 700 {} \\;");
            if (! $process->successful()) {
                throw new Exception($process->errorOutput());
            }

            // Find all files and set permissions to 600
            $process = Process::run("find {$sshPath} -type f -exec chmod 600 {} \\;");
            if (! $process->successful()) {
                throw new Exception($process->errorOutput());
            }

            return [
                'success' => true,
                'message' => 'SSH directory permissions updated successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update SSH directory permissions: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Start the SSH service
     */
    public function startSshService(): array
    {
        try {
            $process = Process::run('sudo systemctl start sshd && sudo systemctl enable sshd');

            if ($process->successful()) {
                return [
                    'success' => true,
                    'message' => 'SSH service started and enabled successfully',
                ];
            } else {
                throw new Exception($process->errorOutput());
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start SSH service: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Stop the SSH service
     */
    public function stopSshService(): array
    {
        try {
            $process = Process::run('sudo systemctl stop sshd && sudo systemctl disable sshd');

            if ($process->successful()) {
                return [
                    'success' => true,
                    'message' => 'SSH service stopped and disabled successfully',
                ];
            } else {
                throw new Exception($process->errorOutput());
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to stop SSH service: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save a private key to a temporary file
     */
    protected function savePrivateKeyToTempFile(SshKey $sshKey): string
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'ssh_private_key_');
        file_put_contents($tempFilePath, $sshKey->private_key);
        chmod($tempFilePath, 0600);

        return $tempFilePath;
    }

    /**
     * Sync the database hosts to config files
     */
    public function syncHostsToConfigFiles(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $configDPath = "{$homePath}/.ssh/config.d";

            // Make sure the directory exists
            if (! is_dir($configDPath)) {
                mkdir($configDPath, 0700, true);
            }

            // Get all active hosts
            $hosts = SshHost::where('active', true)->get();

            // Clean the directory (remove all existing config files)
            $files = glob("{$configDPath}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // Create config files for each host
            foreach ($hosts as $host) {
                $configContent = $host->toSshConfigFormat();
                file_put_contents("{$configDPath}/{$host->name}", $configContent);
                chmod("{$configDPath}/{$host->name}", 0600);
            }

            return [
                'success' => true,
                'message' => 'Host configuration files synchronized successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to synchronize host configuration files: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync the database keys to SSH key files
     */
    public function syncKeysToKeyFiles(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";

            // Make sure the directory exists
            if (! is_dir($sshPath)) {
                mkdir($sshPath, 0700, true);
            }

            // Get all active keys
            $keys = SshKey::where('active', true)->get();

            // Create key files for each key
            foreach ($keys as $key) {
                file_put_contents("{$sshPath}/{$key->name}", $key->private_key);
                chmod("{$sshPath}/{$key->name}", 0600);

                file_put_contents("{$sshPath}/{$key->name}.pub", $key->public_key);
                chmod("{$sshPath}/{$key->name}.pub", 0644);
            }

            return [
                'success' => true,
                'message' => 'SSH key files synchronized successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to synchronize SSH key files: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import existing SSH hosts from config.d directory
     */
    public function importHostsFromConfigFiles(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $configDPath = "{$homePath}/.ssh/config.d";

            if (! is_dir($configDPath)) {
                return [
                    'success' => false,
                    'message' => 'Config directory does not exist: ' . $configDPath,
                ];
            }

            $files = glob("{$configDPath}/*");
            $imported = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    $content = file_get_contents($file);
                    $name = basename($file);

                    // Skip if host already exists
                    if (SshHost::where('name', $name)->exists()) {
                        continue;
                    }

                    // Parse config file
                    $hostname = null;
                    $port = 22;
                    $user = 'root';
                    $identityFile = null;

                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (preg_match('/^Hostname\s+(.+)$/i', $line, $matches)) {
                            $hostname = $matches[1];
                        } elseif (preg_match('/^Port\s+(\d+)$/i', $line, $matches)) {
                            $port = (int) $matches[1];
                        } elseif (preg_match('/^User\s+(.+)$/i', $line, $matches)) {
                            $user = $matches[1];
                        } elseif (preg_match('/^IdentityFile\s+(.+)$/i', $line, $matches)) {
                            $identityFile = basename($matches[1]);
                        }
                    }

                    if ($hostname) {
                        SshHost::create([
                            'name' => $name,
                            'hostname' => $hostname,
                            'port' => $port,
                            'user' => $user,
                            'identity_file' => $identityFile,
                            'active' => true,
                        ]);
                        $imported++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Imported {$imported} SSH hosts from config files",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import SSH hosts: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import existing SSH keys from the .ssh directory
     */
    public function importKeysFromFiles(): array
    {
        try {
            $homePath = app(SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";

            if (! is_dir($sshPath)) {
                return [
                    'success' => false,
                    'message' => 'SSH directory does not exist: ' . $sshPath,
                ];
            }

            $pubKeyFiles = glob("{$sshPath}/*.pub");
            $imported = 0;

            foreach ($pubKeyFiles as $pubKeyFile) {
                $baseName = basename($pubKeyFile, '.pub');
                $privateKeyFile = "{$sshPath}/{$baseName}";

                // Skip if key already exists in the database
                if (SshKey::where('name', $baseName)->exists()) {
                    continue;
                }

                // Skip if private key doesn't exist
                if (! file_exists($privateKeyFile)) {
                    continue;
                }

                $publicKey = file_get_contents($pubKeyFile);
                $privateKey = file_get_contents($privateKeyFile);

                // Extract comment from public key
                $comment = '';
                if (preg_match('/\s+(.+)$/', $publicKey, $matches)) {
                    $comment = $matches[1];
                }

                // Determine key type
                $type = 'ed25519';
                if (strpos($publicKey, 'ssh-rsa') === 0) {
                    $type = 'rsa';
                } elseif (strpos($publicKey, 'ssh-ed25519') === 0) {
                    $type = 'ed25519';
                } elseif (strpos($publicKey, 'ecdsa') !== false) {
                    $type = 'ecdsa';
                } elseif (strpos($publicKey, 'dsa') !== false) {
                    $type = 'dsa';
                }

                SshKey::create([
                    'name' => $baseName,
                    'public_key' => $publicKey,
                    'private_key' => $privateKey,
                    'comment' => $comment,
                    'type' => $type,
                    'active' => true,
                ]);

                $imported++;
            }

            return [
                'success' => true,
                'message' => "Imported {$imported} SSH keys from files",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import SSH keys: ' . $e->getMessage(),
            ];
        }
    }
}
