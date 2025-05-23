<?php

use App\Models\SshKey;

describe('SshKey Model', function () {
    beforeEach(function () {
        $this->key = SshKey::factory()->create([
            'name' => 'test_key',
            'type' => 'ed25519',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG... test@example.com',
            'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----...',
            'comment' => 'test@example.com',
            'active' => true,
        ]);
    });

    it('can create an SSH key', function () {
        expect($this->key)->toBeInstanceOf(SshKey::class)
            ->and($this->key->name)->toBe('test_key')
            ->and($this->key->type)->toBe('ed25519')
            ->and($this->key->active)->toBeTrue()
            ->and($this->key->comment)->toBe('test@example.com');
    });

    it('has fillable attributes', function () {
        $fillable = ['name', 'public_key', 'private_key', 'comment', 'type', 'active'];
        expect($this->key->getFillable())->toBe($fillable);
    });

    it('casts active to boolean', function () {
        expect($this->key->getCasts())->toHaveKey('active')
            ->and($this->key->getCasts()['active'])->toBe('boolean');
    });

    it('includes private key in array/json output', function () {
        $array = $this->key->toArray();
        expect($array)->toHaveKey('private_key');

        $json = $this->key->toJson();
        expect($json)->toContain('private_key');
    });

    it('has fingerprint method available', function () {
        expect(method_exists($this->key, 'getFingerprint'))->toBeTrue();
    });

    it('validates key types', function () {
        $validTypes = ['rsa', 'ed25519', 'ecdsa', 'dsa'];

        foreach ($validTypes as $type) {
            $key = SshKey::factory()->create(['type' => $type]);
            expect($key->type)->toBe($type);
        }
    });

    it('can filter active keys', function () {
        SshKey::factory()->create(['active' => false]);
        SshKey::factory()->create(['active' => true]);

        $activeKeys = SshKey::where('active', true)->get();
        expect($activeKeys)->toHaveCount(2); // Including the one from beforeEach

        foreach ($activeKeys as $key) {
            expect($key->active)->toBeTrue();
        }
    });

    it('can scope by type', function () {
        SshKey::factory()->create(['type' => 'rsa']);
        SshKey::factory()->create(['type' => 'ed25519']);

        $ed25519Keys = SshKey::where('type', 'ed25519')->get();
        expect($ed25519Keys)->toHaveCount(2); // Including the one from beforeEach

        foreach ($ed25519Keys as $key) {
            expect($key->type)->toBe('ed25519');
        }
    });

    it('extracts comment from public key automatically', function () {
        $keyWithComment = SshKey::factory()->create([
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG... auto@example.com',
        ]);

        expect($keyWithComment->getCommentFromPublicKey())->toBe('auto@example.com');
    });

    it('validates required fields', function () {
        expect(fn () => SshKey::create([]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can find by name', function () {
        $foundKey = SshKey::where('name', 'test_key')->first();
        expect($foundKey)->not->toBeNull()
            ->and($foundKey->id)->toBe($this->key->id);
    });
});
