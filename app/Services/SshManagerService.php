<?php

namespace App\Services;

use App\Models\SshKey;
use App\Models\SshConfig;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class SshManagerService
{
    /**
     * Get the user's home directory path
     */
    public function getHomeDir(): string
    {
        return env('HOME_DIR', $_SERVER['HOME'] ?? '/home/' . get_current_user());
    }

    /**
     * Get the SSH directory path
     */
    public function getSshDir(): string
    {
        return $this->getHomeDir() . '/.ssh';
    }

    /**
     * Initialize the SSH directory structure
     */
    public function initializeSshDirectory(): array
    {
        $sshDir = $this->getSshDir();
        $configDDir = $sshDir . '/config.d';
        $result = [];

        // Create .ssh directory if it doesn't exist
        if (!is_dir($sshDir)) {
            mkdir($sshDir, 0700, true);
            $result[] = "Created {$sshDir}";
        }

        // Create authorized_keys file if it doesn't exist
        if (!file_exists($sshDir . '/authorized_keys')) {
            touch($sshDir . '/authorized_keys');
            chmod($sshDir . '/authorized_keys', 0600);
            $result[] = "Created {$sshDir}/authorized_keys";
        }

        // Create config.d directory if it doesn't exist
        if (!is_dir($configDDir)) {
            mkdir($configDDir, 0700, true);
            $result[] = "Created {$configDDir}";
        }

        // Create the main config file if it doesn't exist
        if (!file_exists($sshDir . '/config')) {
            $configContent = '# Created by SSHM on ' . date('Ymd') . "\n";
            $configContent .=
                "Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-gcm@openssh.com,aes256-gcm@openssh.com,chacha20-poly1305@openssh.com\n\n";
            $configContent .= "Include ~/.ssh/config.d/*\n\n";
            $configContent .= "Host *\n";
            $configContent .= "  TCPKeepAlive yes\n";
            $configContent .= "  ServerAliveInterval 30\n";
            $configContent .= "  ForwardAgent yes\n";
            $configContent .= "  AddKeysToAgent yes\n";
            $configContent .= "  IdentitiesOnly yes\n";

            file_put_contents($sshDir . '/config', $configContent);
            chmod($sshDir . '/config', 0600);
            $result[] = "Created {$sshDir}/config";
        }

        // Set proper permissions for the SSH directory
        $this->setPermissions();
        $result[] = 'Updated permissions for ~/.ssh';

        return $result;
    }

    /**
     * Set proper permissions for SSH files and directories
     */
    public function setPermissions(): void
    {
        $sshDir = $this->getSshDir();

        // Find all directories and set 700 permissions
        $directories = Process::run('find ' . escapeshellarg($sshDir) . ' -type d');
        if ($directories->successful()) {
            $dirList = explode("\n", trim($directories->output()));
            foreach ($dirList as $dir) {
                if (!empty($dir)) {
                    chmod($dir, 0700);
                }
            }
        }

        // Find all files and set 600 permissions
        $files = Process::run('find ' . escapeshellarg($sshDir) . ' -type f');
        if ($files->successful()) {
            $fileList = explode("\n", trim($files->output()));
            foreach ($fileList as $file) {
                if (!empty($file)) {
                    chmod($file, 0600);
                }
            }
        }
    }

    /**
     * Create a new SSH host entry in config.d
     */
    public function createHost(
        string $name,
        string $host,
        int $port = 22,
        string $user = 'root',
        ?string $skey = null,
    ): bool {
        $sshDir = $this->getSshDir();
        $configPath = "{$sshDir}/config.d/{$name}";

        // Create the config content
        $content = "Host {$name}\n";
        $content .= "  Hostname {$host}\n";
        $content .= "  Port {$port}\n";
        $content .= "  User {$user}\n";

        if ($skey && $skey !== 'none') {
            $content .= "  IdentityFile {$skey}\n";
        } else {
            $content .= "  #IdentityFile none\n";
        }

        // Write the file
        $result = file_put_contents($configPath, $content);

        // Set permissions
        if ($result !== false) {
            chmod($configPath, 0600);
            return true;
        }

        return false;
    }

    /**
     * Read a host entry
     */
    public function readHost(string $name): ?string
    {
        $sshDir = $this->getSshDir();
        $configPath = "{$sshDir}/config.d/{$name}";

        if (file_exists($configPath)) {
            return file_get_contents($configPath);
        }

        return null;
    }

    /**
     * Delete a host entry
     */
    public function deleteHost(string $name): bool
    {
        $sshDir = $this->getSshDir();
        $configPath = "{$sshDir}/config.d/{$name}";

        if (file_exists($configPath)) {
            return unlink($configPath);
        }

        return false;
    }

    /**
     * List all host entries
     */
    public function listHosts(): array
    {
        $sshDir = $this->getSshDir();
        $configDir = "{$sshDir}/config.d";
        $hosts = [];

        if (is_dir($configDir)) {
            $files = glob("{$configDir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    $content = file_get_contents($file);
                    $name = basename($file);

                    // Extract host details using regex
                    preg_match('/Host\s+(\S+)/', $content, $hostMatch);
                    preg_match('/Hostname\s+(\S+)/', $content, $hostnameMatch);
                    preg_match('/Port\s+(\d+)/', $content, $portMatch);
                    preg_match('/User\s+(\S+)/', $content, $userMatch);
                    preg_match('/IdentityFile\s+(\S+)/', $content, $keyMatch);

                    $hosts[$name] = [
                        'name' => $hostMatch[1] ?? $name,
                        'hostname' => $hostnameMatch[1] ?? '',
                        'port' => (int) ($portMatch[1] ?? 22),
                        'user' => $userMatch[1] ?? 'root',
                        'key' => $keyMatch[1] ?? null,
                    ];
                }
            }
        }

        return $hosts;
    }

    /**
     * Create a new SSH key
     */
    public function createKey(string $name, string $comment = '', string $password = ''): array
    {
        $sshDir = $this->getSshDir();
        $keyPath = "{$sshDir}/{$name}";

        // Check if key already exists
        if (file_exists($keyPath)) {
            throw new \Exception("SSH Key '{$keyPath}' already exists");
        }

        // Use default comment if empty
        if (empty($comment)) {
            $hostname = Process::run('hostname')->output();
            $comment = trim($hostname) . '@lan';
        }

        // Build the ssh-keygen command
        $command =
            'ssh-keygen -o -a 100 -t ed25519 -f ' .
            escapeshellarg($keyPath) .
            ' -C ' .
            escapeshellarg($comment);

        // Add password if provided
        if (!empty($password)) {
            $command .= ' -N ' . escapeshellarg($password);
        } else {
            $command .= " -N ''";
        }

        // Execute the command
        $process = Process::run($command);

        if (!$process->successful()) {
            throw new \Exception('Failed to create SSH key: ' . $process->errorOutput());
        }

        // Get key details
        return $this->getKeyDetails($name);
    }

    /**
     * Get details of an SSH key
     */
    public function getKeyDetails(string $name): array
    {
        $sshDir = $this->getSshDir();
        $keyPath = "{$sshDir}/{$name}";
        $pubKeyPath = "{$keyPath}.pub";

        if (!file_exists($pubKeyPath)) {
            throw new \Exception("Public key file '{$pubKeyPath}' not found");
        }

        // Read the public key
        $publicKey = file_get_contents($pubKeyPath);

        // Get key info using ssh-keygen
        $process = Process::run('ssh-keygen -lf ' . escapeshellarg($pubKeyPath));

        if (!$process->successful()) {
            throw new \Exception('Failed to get key details: ' . $process->errorOutput());
        }

        $keyInfoOutput = trim($process->output());

        // Parse key info (format: bits algorithm:fingerprint comment)
        preg_match('/^(\d+)\s+(\S+):(\S+)\s+(.+)$/', $keyInfoOutput, $matches);

        if (count($matches) < 5) {
            throw new \Exception('Failed to parse key details');
        }

        // Extract comment from the public key itself as it might be more reliable
        $pubKeyParts = explode(' ', trim($publicKey));
        $comment = count($pubKeyParts) > 2 ? $pubKeyParts[2] : '';

        return [
            'name' => $name,
            'path' => $keyPath,
            'algorithm' => $matches[2] ?? '',
            'bits' => (int) ($matches[1] ?? 0),
            'fingerprint' => $matches[3] ?? '',
            'comment' => $comment,
            'has_password' => $this->keyHasPassword($keyPath),
        ];
    }

    /**
     * Check if a key has a password
     */
    private function keyHasPassword(string $keyPath): bool
    {
        if (!file_exists($keyPath)) {
            return false;
        }

        // Read the first few bytes to check the encryption header
        $handle = fopen($keyPath, 'r');
        $header = fread($handle, 64);
        fclose($handle);

        // If the key contains "ENCRYPTED", it has a password
        return strpos($header, 'ENCRYPTED') !== false;
    }

    /**
     * Delete an SSH key
     */
    public function deleteKey(string $name): bool
    {
        $sshDir = $this->getSshDir();
        $keyPath = "{$sshDir}/{$name}";
        $pubKeyPath = "{$keyPath}.pub";

        $success = true;

        if (file_exists($keyPath)) {
            $success = $success && unlink($keyPath);
        }

        if (file_exists($pubKeyPath)) {
            $success = $success && unlink($pubKeyPath);
        }

        return $success;
    }

    /**
     * List all SSH keys
     */
    public function listKeys(): array
    {
        $sshDir = $this->getSshDir();
        $keys = [];

        $pubKeyFiles = glob("{$sshDir}/*.pub");

        foreach ($pubKeyFiles as $pubKeyFile) {
            $name = basename($pubKeyFile, '.pub');

            try {
                $keys[$name] = $this->getKeyDetails($name);
            } catch (\Exception $e) {
                // Skip keys that can't be parsed
                continue;
            }
        }

        return $keys;
    }

    /**
     * Copy an SSH key to a server
     */
    public function copyKeyToServer(string $keyName, string $serverName): bool
    {
        $sshDir = $this->getSshDir();
        $pubKeyPath = "{$sshDir}/{$keyName}.pub";

        if (!file_exists($pubKeyPath)) {
            throw new \Exception("Public key file '{$pubKeyPath}' not found");
        }

        $sshConfig = SshConfig::where('name', $serverName)->first();

        if (!$sshConfig) {
            throw new \Exception("SSH server configuration '{$serverName}' not found");
        }

        // Get the public key content
        $publicKey = trim(file_get_contents($pubKeyPath));

        // Create SSH connection using Spatie SSH
        $ssh = \Spatie\Ssh\Ssh::create($sshConfig->username, $sshConfig->host, $sshConfig->port);

        // Use private key if available, otherwise use password
        if (!empty($sshConfig->private_key_path)) {
            $ssh->usePrivateKey($sshConfig->private_key_path);
        } elseif (!empty($sshConfig->password)) {
            $ssh->usePassword($sshConfig->password);
        }

        // Disable strict host key checking
        $ssh->disableStrictHostKeyChecking();

        // Execute the command to append the key to authorized_keys
        $command = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '{$publicKey}' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys";
        $process = $ssh->execute($command);

        return $process->isSuccessful();
    }

    /**
     * Sync database records with SSH keys from the filesystem
     */
    public function syncKeysWithDatabase(): array
    {
        $fsKeys = $this->listKeys();
        $result = [
            'added' => 0,
            'updated' => 0,
            'removed' => 0,
        ];

        // Add or update keys found in the filesystem
        foreach ($fsKeys as $name => $keyInfo) {
            $sshKey = SshKey::firstOrNew(['name' => $name]);

            $sshKey->comment = $keyInfo['comment'];
            $sshKey->algorithm = $keyInfo['algorithm'];
            $sshKey->bits = $keyInfo['bits'];
            $sshKey->fingerprint = $keyInfo['fingerprint'];
            $sshKey->has_password = $keyInfo['has_password'];
            $sshKey->path = $keyInfo['path'];

            if ($sshKey->exists) {
                $result['updated']++;
            } else {
                $result['added']++;
            }

            $sshKey->save();
        }

        // Remove database records for keys that no longer exist in the filesystem
        $dbKeys = SshKey::all();
        foreach ($dbKeys as $dbKey) {
            if (!isset($fsKeys[$dbKey->name])) {
                $dbKey->delete();
                $result['removed']++;
            }
        }

        return $result;
    }

    /**
     * Sync database records with SSH configs from the filesystem
     */
    public function syncConfigsWithDatabase(): array
    {
        $fsConfigs = $this->listHosts();
        $result = [
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        // Add or update configs found in the filesystem
        foreach ($fsConfigs as $name => $configInfo) {
            // Check if this config is already in the database
            $sshConfig = SshConfig::where('name', $configInfo['name'])->first();

            // Skip if already exists to avoid overwriting any custom settings
            if ($sshConfig) {
                $result['skipped']++;
                continue;
            }

            // Create a new config
            $sshConfig = new SshConfig();
            $sshConfig->name = $configInfo['name'];
            $sshConfig->host = $configInfo['hostname'];
            $sshConfig->port = $configInfo['port'];
            $sshConfig->username = $configInfo['user'];
            $sshConfig->private_key_path = $configInfo['key'];
            $sshConfig->save();

            $result['added']++;
        }

        return $result;
    }
}
