<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use App\Models\SshKey;
use App\Services\SshService;
use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshKeys extends ListRecords
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
            // Dropdown menu for all actions except Create
            ActionGroup::make([
                Action::make('generateKey')
                    ->label('Generate New Key')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->modalHeading('Generate New SSH Key')
                    ->modalDescription('Generate a new SSH key pair with the following settings:')
                    ->modalSubmitActionLabel('Generate Key')
                    ->schema([
                        TextInput::make('name')
                            ->label('Key Name')
                            ->required()
                            ->unique('ssh_keys', 'name')
                            ->maxLength(255)
                            ->placeholder('id_ed25519')
                            ->helperText('The name of the key file (e.g., id_ed25519)'),

                        TextInput::make('comment')
                            ->label('Comment')
                            ->maxLength(255)
                            ->default(function () {
                                $user = trim(shell_exec('whoami') ?: 'user');
                                $host = trim(shell_exec('hostname') ?: 'localhost');

                                return "{$user}@{$host}";
                            })
                            ->placeholder('user@hostname')
                            ->helperText('A comment to help identify this key'),

                        TextInput::make('password')
                            ->label('Password (Optional)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Leave empty for no password')
                            ->helperText('A password to protect the private key (optional)'),

                        Select::make('type')
                            ->label('Key Type')
                            ->options([
                                'ed25519' => 'ED25519 (Recommended)',
                                'rsa' => 'RSA',
                                'ecdsa' => 'ECDSA',
                            ])
                            ->default('ed25519')
                            ->required()
                            ->helperText('ED25519 is recommended for new keys'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $key = SshKey::generateKeyPair(
                                $data['name'],
                                $data['comment'] ?? '',
                                $data['password'] ?? '',
                                $data['type'] ?? 'ed25519'
                            );

                            Notification::make()
                                ->success()
                                ->title('SSH Key Generated')
                                ->body("Successfully generated {$data['type']} key pair named {$data['name']}")
                                ->send();

                            $this->resetTable();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Key Generation Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('importFromFiles')
                    ->label('Import from Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->importKeysFromFiles();

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

                Action::make('syncAllToFiles')
                    ->label('Sync All to Files')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function () {
                        $sshService = app(SshService::class);
                        $result = $sshService->syncKeysToKeyFiles();

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
            ])
                ->label('SSH Key Actions')
                ->icon('heroicon-o-key'),

            // Create button positioned last (on the right)
            CreateAction::make()
                ->label('New SSH Key')
                ->modalHeading('Create SSH Key')
                ->icon('heroicon-o-plus')
                ->mutateDataUsing(function (array $data): array {
                    // Set defaults if needed
                    $data['active'] = $data['active'] ?? true;

                    return $data;
                }),
        ];
    }
}
