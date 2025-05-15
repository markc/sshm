<?php

namespace App\Filament\Resources\SshConfigResource\Pages;

use App\Filament\Resources\SshConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSshConfig extends EditRecord
{
    protected static string $resource = SshConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
