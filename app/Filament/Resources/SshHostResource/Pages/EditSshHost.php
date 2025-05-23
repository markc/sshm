<?php

namespace App\Filament\Resources\SshHostResource\Pages;

use App\Filament\Resources\SshHostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSshHost extends EditRecord
{
    protected static string $resource = SshHostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
