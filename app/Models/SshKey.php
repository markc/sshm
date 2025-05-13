<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SshKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'comment',
        'algorithm',
        'bits',
        'fingerprint',
        'has_password',
        'path',
    ];

    protected $casts = [
        'bits' => 'integer',
        'has_password' => 'boolean',
    ];
}
