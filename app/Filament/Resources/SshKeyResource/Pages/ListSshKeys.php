<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use App\Models\SshKey;
use App\Services\SshService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSshKeys extends ListRecords
{
    protected static string $resource = SshKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Dropdown menu for all actions except Create
            Actions\ActionGroup::make([
                Actions\Action::make('generateKey')
                    ->label('Generate New Key')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->modalHeading('Generate New SSH Key')
                    ->modalDescription('Generate a new SSH key pair with the following settings:')
                    ->modalSubmitActionLabel('Generate Key')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Key Name')
                            ->required()
                            ->unique('ssh_keys', 'name')
                            ->maxLength(255)
                            ->placeholder('id_ed25519')
                            ->helperText('The name of the key file (e.g., id_ed25519)'),

                        Forms\Components\TextInput::make('comment')
                            ->label('Comment')
                            ->maxLength(255)
                            ->default(function () {
                                $user = trim(shell_exec('whoami') ?: 'user');
                                $host = trim(shell_exec('hostname') ?: 'localhost');

                                return "{$user}@{$host}";
                            })
                            ->placeholder('user@hostname')
                            ->helperText('A comment to help identify this key'),

                        Forms\Components\TextInput::make('password')
                            ->label('Password (Optional)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Leave empty for no password')
                            ->helperText('A password to protect the private key (optional)'),

                        Forms\Components\Select::make('type')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Key Generation Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Actions\Action::make('importFromFiles')
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

                Actions\Action::make('syncAllToFiles')
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
            Actions\CreateAction::make()
                ->label('New SSH Key')
                ->modalHeading('Create SSH Key')
                ->icon('heroicon-o-plus')
                ->mutateFormDataUsing(function (array $data): array {
                    // Set defaults if needed
                    $data['active'] = $data['active'] ?? true;

                    return $data;
                }),
        ];
    }
}
