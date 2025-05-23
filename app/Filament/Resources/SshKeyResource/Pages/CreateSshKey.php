<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSshKey extends CreateRecord
{
    protected static string $resource = SshKeyResource::class;
}
