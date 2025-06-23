<?php

namespace App\Settings;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SshSettings
{
    public string $home_dir = '/home/user';

    public string $default_user = 'root';

    public int $default_port = 22;

    public string $default_key_type = 'ed25519';

    public bool $strict_host_checking = false;

    public ?string $default_ssh_host = null;

    public ?string $default_ssh_key = null;

    public int $timeout = 300;

    public function __construct(array $attributes = [])
    {
        if (isset($attributes['home_dir'])) {
            $this->home_dir = $attributes['home_dir'];
        }
        if (isset($attributes['default_user'])) {
            $this->default_user = $attributes['default_user'];
        }
        if (isset($attributes['default_port'])) {
            $this->default_port = (int) $attributes['default_port'];
        }
        if (isset($attributes['default_key_type'])) {
            $this->default_key_type = $attributes['default_key_type'];
        }
        if (isset($attributes['strict_host_checking'])) {
            $this->strict_host_checking = (bool) $attributes['strict_host_checking'];
        }
        if (isset($attributes['default_ssh_host'])) {
            $this->default_ssh_host = $attributes['default_ssh_host'];
        }
        if (isset($attributes['default_ssh_key'])) {
            $this->default_ssh_key = $attributes['default_ssh_key'];
        }
        if (isset($attributes['timeout'])) {
            $this->timeout = (int) $attributes['timeout'];
        }
    }

    public function getHomeDir(): string
    {
        return $this->home_dir;
    }

    public function getDefaultUser(): string
    {
        return $this->default_user;
    }

    public function getDefaultPort(): int
    {
        return $this->default_port;
    }

    public function getDefaultKeyType(): string
    {
        return $this->default_key_type;
    }

    public function getStrictHostChecking(): bool
    {
        return $this->strict_host_checking;
    }

    public function getDefaultSshHost(): ?string
    {
        return $this->default_ssh_host;
    }

    public function getDefaultSshKey(): ?string
    {
        return $this->default_ssh_key;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function save(array $data = []): void
    {
        // Update properties if data is provided
        if (! empty($data)) {
            if (isset($data['home_dir'])) {
                $this->home_dir = $data['home_dir'];
            }
            if (isset($data['default_user'])) {
                $this->default_user = $data['default_user'];
            }
            if (isset($data['default_port'])) {
                $this->default_port = (int) $data['default_port'];
            }
            if (isset($data['default_key_type'])) {
                $this->default_key_type = $data['default_key_type'];
            }
            if (isset($data['strict_host_checking'])) {
                $this->strict_host_checking = (bool) $data['strict_host_checking'];
            }
            if (isset($data['default_ssh_host'])) {
                $this->default_ssh_host = $data['default_ssh_host'];
            }
            if (isset($data['default_ssh_key'])) {
                $this->default_ssh_key = $data['default_ssh_key'];
            }
            if (isset($data['timeout'])) {
                $this->timeout = (int) $data['timeout'];
            }
        }

        // Save to database if possible
        try {
            $this->saveToDatabase();
        } catch (Exception $e) {
            // Fallback to .env file
            $this->saveToEnvFile();
        }
    }

    protected function saveToDatabase(): void
    {
        if (! Schema::hasTable('settings')) {
            throw new Exception('Settings table does not exist');
        }

        $settings = [
            ['group' => 'ssh', 'name' => 'home_dir', 'value' => $this->home_dir],
            ['group' => 'ssh', 'name' => 'default_user', 'value' => $this->default_user],
            ['group' => 'ssh', 'name' => 'default_port', 'value' => $this->default_port],
            ['group' => 'ssh', 'name' => 'default_key_type', 'value' => $this->default_key_type],
            ['group' => 'ssh', 'name' => 'strict_host_checking', 'value' => $this->strict_host_checking],
            ['group' => 'ssh', 'name' => 'default_ssh_host', 'value' => $this->default_ssh_host],
            ['group' => 'ssh', 'name' => 'default_ssh_key', 'value' => $this->default_ssh_key],
            ['group' => 'ssh', 'name' => 'timeout', 'value' => $this->timeout],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')
                ->updateOrInsert(
                    ['group' => $setting['group'], 'name' => $setting['name']],
                    ['value' => json_encode($setting['value'])]
                );
        }
    }

    protected function saveToEnvFile(): void
    {
        $envPath = app()->environmentFilePath();
        $envContent = file_get_contents($envPath);

        $values = [
            'SSH_HOME_DIR' => $this->home_dir,
            'SSH_DEFAULT_USER' => $this->default_user,
            'SSH_DEFAULT_PORT' => $this->default_port,
            'SSH_DEFAULT_KEY_TYPE' => $this->default_key_type,
            'SSH_STRICT_HOST_CHECKING' => $this->strict_host_checking ? 'true' : 'false',
            'SSH_DEFAULT_HOST' => $this->default_ssh_host,
            'SSH_DEFAULT_KEY' => $this->default_ssh_key,
        ];

        foreach ($values as $key => $value) {
            // Format the value appropriately for .env
            if (is_string($value)) {
                $value = "\"$value\"";
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = 'null';
            }

            // Check if the key exists in .env
            if (preg_match("/^$key=/m", $envContent)) {
                // Replace existing value
                $envContent = preg_replace("/^$key=.*/m", "$key=$value", $envContent);
            } else {
                // Add new value
                $envContent .= PHP_EOL . "$key=$value";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
