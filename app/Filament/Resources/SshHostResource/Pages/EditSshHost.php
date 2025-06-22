<?php

namespace App\Filament\Resources\SshHostResource\Pages;

use App\Filament\Resources\SshHostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSshHost extends EditRecord
{
    protected static string $resource = SshHostResource::class;

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumb(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
