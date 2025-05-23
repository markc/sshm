<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Process;

class SshKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'public_key',
        'private_key',
        'comment',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function sshHosts(): HasMany
    {
        return $this->hasMany(SshHost::class, 'identity_file', 'name');
    }

    public static function generateKeyPair(string $name, string $comment = '', string $password = '', string $type = 'ed25519'): self
    {
        $tempDir = sys_get_temp_dir() . '/' . uniqid('ssh_key_', true);
        mkdir($tempDir, 0700);
        
        $keyPath = "{$tempDir}/{$name}";
        
        $command = [
            'ssh-keygen',
            '-o',
            '-a', '100',
            '-t', $type,
            '-f', $keyPath,
            '-C', $comment,
            '-N', $password
        ];
        
        $process = Process::run(implode(' ', array_map('escapeshellarg', $command)));
        
        if (!$process->successful()) {
            throw new \Exception("Failed to generate SSH key: " . $process->errorOutput());
        }
        
        $privateKey = file_get_contents($keyPath);
        $publicKey = file_get_contents("{$keyPath}.pub");
        
        // Clean up
        unlink($keyPath);
        unlink("{$keyPath}.pub");
        rmdir($tempDir);
        
        return self::create([
            'name' => $name,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'comment' => $comment,
            'type' => $type,
            'active' => true,
        ]);
    }

    public function getFingerprint(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
        file_put_contents($tempFile, $this->public_key);
        
        $process = Process::run("ssh-keygen -lf " . escapeshellarg($tempFile));
        
        unlink($tempFile);
        
        if (!$process->successful()) {
            throw new \Exception("Failed to get fingerprint: " . $process->errorOutput());
        }
        
        return trim($process->output());
    }
    
    public function getCommentFromPublicKey(): string
    {
        $parts = explode(' ', trim($this->public_key));
        if (count($parts) >= 3) {
            return $parts[2];
        }
        return $this->comment ?: '';
    }
    
    protected static function booted()
    {
        static::creating(function ($sshKey) {
            // If no comment is provided but public key exists, extract comment from public key
            if (empty($sshKey->comment) && !empty($sshKey->public_key)) {
                $parts = explode(' ', trim($sshKey->public_key));
                if (count($parts) >= 3) {
                    $sshKey->comment = $parts[2];
                }
            }
        });
        
        static::updating(function ($sshKey) {
            // Update comment when public key changes
            if ($sshKey->isDirty('public_key')) {
                $parts = explode(' ', trim($sshKey->public_key));
                if (count($parts) >= 3) {
                    $sshKey->comment = $parts[2];
                }
            }
        });
    }
}
