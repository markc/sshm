<?php

namespace App\Models;

use App\Settings\SshSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SshHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'port',
        'user',
        'identity_file',
        'active',
    ];

    protected $casts = [
        'port' => 'integer',
        'active' => 'boolean',
    ];

    public function sshKey(): BelongsTo
    {
        return $this->belongsTo(SshKey::class, 'identity_file', 'name');
    }

    public function getConnectionString(): string
    {
        return "{$this->user}@{$this->hostname}:{$this->port}";
    }

    public function toSshConfigFormat(): string
    {
        $homePath = app(SshSettings::class)->getHomeDir();

        $config = "Host {$this->name}\n";
        $config .= "  Hostname {$this->hostname}\n";
        $config .= "  Port {$this->port}\n";
        $config .= "  User {$this->user}\n";

        if ($this->identity_file) {
            $config .= "  IdentityFile {$homePath}/.ssh/{$this->identity_file}\n";
        } else {
            $config .= "  #IdentityFile none\n";
        }

        return $config;
    }
}
