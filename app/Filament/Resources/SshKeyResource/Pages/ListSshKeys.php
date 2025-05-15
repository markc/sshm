<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use App\Services\SshManagerService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshKeys extends ListRecords
{
    protected static string $resource = SshKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('syncKeys')
                ->label('Sync SSH Keys')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $sshManager = new SshManagerService();

                    try {
                        // Initialize SSH directory if it doesn't exist
                        $sshManager->initializeSshDirectory();

                        // Sync the keys
                        $result = $sshManager->syncKeysWithDatabase();

                        $message = "Keys synchronized: {$result['added']} added, {$result['updated']} updated, {$result['removed']} removed";

                        Notification::make()
                            ->title('SSH Keys Synchronized')
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

            Actions\Action::make('initSsh')
                ->label('Initialize SSH Dir')
                ->icon('heroicon-o-folder-plus')
                ->action(function () {
                    $sshManager = new SshManagerService();

                    try {
                        $result = $sshManager->initializeSshDirectory();

                        Notification::make()
                            ->title('SSH Directory Initialized')
                            ->body(implode(', ', $result))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Initialization Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('fixPermissions')
                ->label('Fix SSH Permissions')
                ->icon('heroicon-o-shield-check')
                ->action(function () {
                    $sshManager = new SshManagerService();

                    try {
                        $sshManager->setPermissions();

                        Notification::make()
                            ->title('SSH Permissions Fixed')
                            ->body(
                                'All SSH files and directories have been set to the correct permissions.',
                            )
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Permission Fix Failed')
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
