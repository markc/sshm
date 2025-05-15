<?php

namespace App\Filament\Resources\SshConfigResource\Pages;

use App\Filament\Resources\SshConfigResource;
use App\Services\SshManagerService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshConfigs extends ListRecords
{
    protected static string $resource = SshConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('syncConfigs')
                ->label('Sync Config Files')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $sshManager = new SshManagerService();

                    try {
                        // Initialize SSH directory if it doesn't exist
                        $sshManager->initializeSshDirectory();

                        // Sync the configs
                        $result = $sshManager->syncConfigsWithDatabase();

                        $message = "Config files synchronized: {$result['added']} added, {$result['skipped']} skipped";

                        Notification::make()
                            ->title('SSH Configs Synchronized')
                            ->body($message)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function mount(): void
    {
        // Ensure SSH directory is initialized
        $sshManager = new SshManagerService();
        try {
            $sshManager->initializeSshDirectory();
        } catch (\Exception $e) {
            // Swallow exception - we'll still try to load whatever data we can
        }

        parent::mount();
    }
}
