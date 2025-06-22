<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSshKey extends EditRecord
{
    protected static string $resource = SshKeyResource::class;

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
