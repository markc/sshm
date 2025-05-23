<?php

namespace App\Filament\Resources\SshHostResource\Pages;

use App\Filament\Resources\SshHostResource;
use App\Services\SshService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshHosts extends ListRecords
{
    protected static string $resource = SshHostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Dropdown menu for all actions except Create
            Actions\ActionGroup::make([
                Actions\Action::make('initSshDirectory')
                    ->label('Initialize SSH Directory')
                    ->icon('heroicon-o-folder-plus')
                    ->color('success')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->initSshDirectory();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('SSH Directory Initialized')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Initialization Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),

                Actions\Action::make('updatePermissions')
                    ->label('Update SSH Permissions')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->updatePermissions();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Permissions Updated')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Permission Update Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),

                Actions\Action::make('importFromConfig')
                    ->label('Import from Config Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->importHostsFromConfigFiles();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Import Successful')
                                ->body($result['message'])
                                ->send();

                            $this->resetTable();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Import Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),

                Actions\Action::make('syncAllToConfig')
                    ->label('Sync All to Config')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->syncHostsToConfigFiles();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Sync Successful')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Sync Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),

                Actions\Action::make('sshServiceControl')
                    ->label('SSH Service Control')
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->modalHeading('SSH Service Control')
                    ->modalDescription('Start or stop the SSH service on this server.')
                    ->modalSubmitActionLabel('Apply')
                    ->form([
                        \Filament\Forms\Components\Radio::make('action')
                            ->label('Select Action')
                            ->required()
                            ->options([
                                'start' => 'Start and Enable SSH Service',
                                'stop' => 'Stop and Disable SSH Service',
                            ])
                            ->default('start'),
                    ])
                    ->action(function (array $data) {
                        $sshService = app(SshService::class);

                        if ($data['action'] === 'start') {
                            $result = $sshService->startSshService();
                        } else {
                            $result = $sshService->stopSshService();
                        }

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Service Action Successful')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Service Action Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),
            ])
                ->label('SSH Actions')
                ->icon('heroicon-o-cog-6-tooth'),

            // Create button positioned last (on the right)
            Actions\CreateAction::make(),
        ];
    }
}
