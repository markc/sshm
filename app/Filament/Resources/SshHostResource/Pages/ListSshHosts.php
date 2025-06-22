<?php

namespace App\Filament\Resources\SshHostResource\Pages;

use App\Filament\Resources\SshHostResource;
use App\Services\SshService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshHosts extends ListRecords
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
            // Dropdown menu for all actions except Create
            ActionGroup::make([
                Action::make('initSshDirectory')
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

                Action::make('updatePermissions')
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

                Action::make('importFromConfig')
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

                Action::make('syncAllToConfig')
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

                Action::make('sshServiceControl')
                    ->label('SSH Service Control')
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->modalHeading('SSH Service Control')
                    ->modalDescription('Start or stop the SSH service on this server.')
                    ->modalSubmitActionLabel('Apply')
                    ->schema([
                        Radio::make('action')
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
            CreateAction::make()
                ->label('New SSH Host')
                ->modalHeading('Create SSH Host')
                ->icon('heroicon-o-plus')
                ->mutateDataUsing(function (array $data): array {
                    // Set defaults if needed
                    $data['port'] = $data['port'] ?? 22;
                    $data['user'] = $data['user'] ?? 'root';
                    $data['active'] = $data['active'] ?? true;

                    return $data;
                }),
        ];
    }
}
