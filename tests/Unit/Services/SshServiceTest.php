<?php

use App\Models\SshHost;
use App\Models\SshKey;
use App\Services\SshService;
use App\Settings\SshSettings;
use Illuminate\Support\Facades\Process;
use Mockery\MockInterface;

describe('SshService', function () {
    beforeEach(function () {
        $this->sshService = new SshService();

        $this->host = SshHost::factory()->create([
            'name' => 'test-host',
            'hostname' => 'localhost',
            'port' => 22,
            'user' => 'testuser',
            'identity_file' => 'test_key',
            'active' => true,
        ]);

        $this->key = SshKey::factory()->create([
            'name' => 'test_key',
            'type' => 'ed25519',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG...',
            'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----...',
            'active' => true,
        ]);

        // Mock SSH settings
        $this->mock(SshSettings::class, function (MockInterface $mock) {
            $mock->shouldReceive('getHomeDir')->andReturn('/tmp/test');
        });
    });

    it('can initialize SSH directory structure', function () {
        $result = $this->sshService->initSshDirectory();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('message');
    });

    it('handles SSH directory initialization failure', function () {
        // Mock settings to return invalid path
        $this->mock(SshSettings::class, function (MockInterface $mock) {
            $mock->shouldReceive('getHomeDir')->andReturn('/invalid/path/that/cannot/be/created');
        });

        $result = $this->sshService->initSshDirectory();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Failed to initialize');
    });

    it('can update SSH permissions', function () {
        Process::fake([
            'find * -type d -exec chmod 700 {} \;' => Process::result('', '', 0),
            'find * -type f -exec chmod 600 {} \;' => Process::result('', '', 0),
        ]);

        $result = $this->sshService->updatePermissions();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('updated successfully');
    });

    it('handles permission update failure', function () {
        Process::fake([
            'find * -type d -exec chmod 700 {} \;' => Process::result('', 'Permission denied', 1),
        ]);

        $result = $this->sshService->updatePermissions();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Failed to update');
    });

    it('can start SSH service', function () {
        Process::fake([
            'sudo systemctl start sshd && sudo systemctl enable sshd' => Process::result('', '', 0),
        ]);

        $result = $this->sshService->startSshService();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('started and enabled');
    });

    it('can stop SSH service', function () {
        Process::fake([
            'sudo systemctl stop sshd && sudo systemctl disable sshd' => Process::result('', '', 0),
        ]);

        $result = $this->sshService->stopSshService();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('stopped and disabled');
    });

    it('can save private key to temporary file', function () {
        $reflection = new ReflectionClass($this->sshService);
        $method = $reflection->getMethod('savePrivateKeyToTempFile');
        $method->setAccessible(true);

        $tempPath = $method->invoke($this->sshService, $this->key);

        expect($tempPath)->toBeString()
            ->and(file_exists($tempPath))->toBeTrue()
            ->and(file_get_contents($tempPath))->toBe($this->key->private_key);

        // Clean up
        unlink($tempPath);
    });

    it('can sync hosts to config files', function () {
        $result = $this->sshService->syncHostsToConfigFiles();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('synchronized successfully');
    });

    it('can sync keys to key files', function () {
        $result = $this->sshService->syncKeysToKeyFiles();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('synchronized successfully');
    });

    it('can import hosts from config files', function () {
        // Create a test config file
        $configDir = '/tmp/test/.ssh/config.d';
        if (! is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }

        $configContent = "Host test-import\n  Hostname test.example.com\n  Port 2222\n  User admin\n";
        file_put_contents($configDir . '/test-import', $configContent);

        $result = $this->sshService->importHostsFromConfigFiles();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue();

        // Clean up
        unlink($configDir . '/test-import');
    });

    it('can import keys from files', function () {
        // Create test key files
        $sshDir = '/tmp/test/.ssh';
        if (! is_dir($sshDir)) {
            mkdir($sshDir, 0700, true);
        }

        file_put_contents($sshDir . '/test_import', '-----BEGIN OPENSSH PRIVATE KEY-----');
        file_put_contents($sshDir . '/test_import.pub', 'ssh-ed25519 AAAAC3... test@import.com');

        $result = $this->sshService->importKeysFromFiles();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue();

        // Clean up
        unlink($sshDir . '/test_import');
        unlink($sshDir . '/test_import.pub');
    });

    it('validates command parameters for execution', function () {
        $host = SshHost::factory()->create(['hostname' => '']);

        // Should handle empty hostname gracefully
        $result = $this->sshService->executeCommand($host, 'echo test');
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('output');
    });

    it('handles SSH execution errors gracefully', function () {
        $invalidHost = SshHost::factory()->create([
            'hostname' => 'invalid-host-that-does-not-exist',
            'port' => 22,
            'user' => 'testuser',
        ]);

        $result = $this->sshService->executeCommand($invalidHost, 'echo test');

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['success', 'output', 'error', 'exit_code'])
            ->and($result['success'])->toBeFalse();
    });
});
