<?php

use App\Models\SshHost;

describe('SshHost Model', function () {
    beforeEach(function () {
        $this->host = SshHost::factory()->create([
            'name' => 'test-host',
            'hostname' => 'example.com',
            'port' => 22,
            'user' => 'root',
            'identity_file' => 'test_key',
            'active' => true,
        ]);
    });

    it('can create an SSH host', function () {
        expect($this->host)->toBeInstanceOf(SshHost::class)
            ->and($this->host->name)->toBe('test-host')
            ->and($this->host->hostname)->toBe('example.com')
            ->and($this->host->port)->toBe(22)
            ->and($this->host->user)->toBe('root')
            ->and($this->host->identity_file)->toBe('test_key')
            ->and($this->host->active)->toBeTrue();
    });

    it('has fillable attributes', function () {
        $fillable = ['name', 'hostname', 'port', 'user', 'identity_file', 'active'];
        expect($this->host->getFillable())->toBe($fillable);
    });

    it('casts active to boolean', function () {
        expect($this->host->getCasts())->toHaveKey('active')
            ->and($this->host->getCasts()['active'])->toBe('boolean');
    });

    it('can generate SSH config format', function () {
        $config = $this->host->toSshConfigFormat();

        expect($config)->toContain('Host test-host')
            ->and($config)->toContain('Hostname example.com')
            ->and($config)->toContain('Port 22')
            ->and($config)->toContain('User root')
            ->and($config)->toContain('IdentityFile')
            ->and($config)->toContain('test_key');
    });

    it('handles optional identity file in SSH config', function () {
        $hostWithoutKey = SshHost::factory()->create([
            'identity_file' => null,
        ]);

        $config = $hostWithoutKey->toSshConfigFormat();
        expect($config)->toContain('#IdentityFile none');
    });

    it('validates required fields', function () {
        expect(fn () => SshHost::create([]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can filter active hosts', function () {
        SshHost::factory()->create(['active' => false]);
        SshHost::factory()->create(['active' => true]);

        $activeHosts = SshHost::where('active', true)->get();
        expect($activeHosts)->toHaveCount(2); // Including the one from beforeEach

        foreach ($activeHosts as $host) {
            expect($host->active)->toBeTrue();
        }
    });

    it('can filter inactive hosts', function () {
        SshHost::factory()->create(['active' => false]);
        SshHost::factory()->create(['active' => true]);

        $inactiveHosts = SshHost::where('active', false)->get();
        expect($inactiveHosts)->toHaveCount(1)
            ->and($inactiveHosts->first()->active)->toBeFalse();
    });

    it('can find by hostname', function () {
        $foundHost = SshHost::where('hostname', 'example.com')->first();
        expect($foundHost)->not->toBeNull()
            ->and($foundHost->id)->toBe($this->host->id);
    });
});
