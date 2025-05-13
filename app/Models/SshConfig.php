<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SshConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'private_key_path',
        'password',
        'is_default',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_default' => 'boolean',
    ];
}
