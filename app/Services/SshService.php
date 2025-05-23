<?php

namespace App\Services;

use App\Models\SshHost;
use App\Models\SshKey;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
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
            $ssh = Ssh::create($host->user, $host->hostname);
            
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
    public function executeCommandWithStreaming(SshHost $host, string $command, callable $outputCallback = null): array
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

            // Create the SSH connection with streaming support
            $ssh = Ssh::create($host->user, $host->hostname);
            
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

            // Set up output streaming if callback provided
            if ($outputCallback) {
                $ssh->onOutput($outputCallback);
            }

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
     * Initialize the SSH directory structure
     */
    public function initSshDirectory(): array
    {
        try {
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";
            $configPath = "{$sshPath}/config";
            $configDPath = "{$sshPath}/config.d";
            $authKeysPath = "{$sshPath}/authorized_keys";
            
            // Create .ssh directory if it doesn't exist
            if (!is_dir($sshPath)) {
                mkdir($sshPath, 0700);
            }
            
            // Create authorized_keys file if it doesn't exist
            if (!file_exists($authKeysPath)) {
                touch($authKeysPath);
                chmod($authKeysPath, 0600);
            }
            
            // Create config.d directory if it doesn't exist
            if (!is_dir($configDPath)) {
                mkdir($configDPath, 0700);
            }
            
            // Create or update config file
            if (!file_exists($configPath)) {
                $configContent = "# Created by SSHM on " . date('Y-m-d') . PHP_EOL;
                $configContent .= "Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-gcm@openssh.com,aes256-gcm@openssh.com,chacha20-poly1305@openssh.com" . PHP_EOL . PHP_EOL;
                $configContent .= "Include ~/.ssh/config.d/*" . PHP_EOL . PHP_EOL;
                $configContent .= "Host *" . PHP_EOL;
                $configContent .= "  TCPKeepAlive yes" . PHP_EOL;
                $configContent .= "  ServerAliveInterval 30" . PHP_EOL;
                $configContent .= "  ForwardAgent yes" . PHP_EOL;
                $configContent .= "  AddKeysToAgent yes" . PHP_EOL;
                $configContent .= "  IdentitiesOnly yes" . PHP_EOL;
                
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
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";
            
            // Find all directories and set permissions to 700
            $process = Process::run("find {$sshPath} -type d -exec chmod 700 {} \\;");
            if (!$process->successful()) {
                throw new Exception($process->errorOutput());
            }
            
            // Find all files and set permissions to 600
            $process = Process::run("find {$sshPath} -type f -exec chmod 600 {} \\;");
            if (!$process->successful()) {
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
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $configDPath = "{$homePath}/.ssh/config.d";
            
            // Make sure the directory exists
            if (!is_dir($configDPath)) {
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
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";
            
            // Make sure the directory exists
            if (!is_dir($sshPath)) {
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
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $configDPath = "{$homePath}/.ssh/config.d";
            
            if (!is_dir($configDPath)) {
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
            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
            $sshPath = "{$homePath}/.ssh";
            
            if (!is_dir($sshPath)) {
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
                if (!file_exists($privateKeyFile)) {
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