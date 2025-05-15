<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages;
use App\Models\SshKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Process;
use Illuminate\Database\Eloquent\Builder;

class SshKeyResource extends Resource
{
    protected static ?string $model = SshKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'SSH Keys';

    protected static ?string $navigationGroup = 'SSH Management';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('SSH Key Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Key Name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('File name without extension (will be saved as ~/.ssh/name)'),

                    Forms\Components\TextInput::make('comment')
                        ->label('Comment')
                        ->helperText('Comment associated with the key (e.g. user@hostname)'),

                    Forms\Components\Toggle::make('has_password')
                        ->label('Use Password?')
                        ->default(false),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->hidden(fn(Forms\Get $get) => !$get('has_password'))
                        ->dehydrated(false)
                        ->visible(fn(string $operation): bool => $operation === 'create'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Key Information')
                ->schema([
                    Forms\Components\TextInput::make('algorithm')
                        ->label('Algorithm')
                        ->disabled()
                        ->visible(fn(string $operation): bool => $operation === 'edit'),

                    Forms\Components\TextInput::make('bits')
                        ->label('Bits')
                        ->disabled()
                        ->visible(fn(string $operation): bool => $operation === 'edit'),

                    Forms\Components\TextInput::make('fingerprint')
                        ->label('Fingerprint')
                        ->disabled()
                        ->columnSpanFull()
                        ->visible(fn(string $operation): bool => $operation === 'edit'),

                    Forms\Components\TextInput::make('path')
                        ->label('Path')
                        ->disabled()
                        ->columnSpanFull()
                        ->visible(fn(string $operation): bool => $operation === 'edit'),
                ])
                ->columns(2)
                ->visible(fn(string $operation): bool => $operation === 'edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Key Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('algorithm')->label('Algorithm')->sortable(),

                Tables\Columns\TextColumn::make('bits')->label('Bits')->sortable(),

                Tables\Columns\TextColumn::make('comment')->label('Comment')->searchable(),

                Tables\Columns\IconColumn::make('has_password')
                    ->label('Password Protected')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                TableAction::make('view_public')
                    ->label('View Public Key')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->action(function (SshKey $record): void {
                        // Logic will be implemented in a custom modal
                    })
                    ->modalContent(function (SshKey $record) {
                        // Get the public key content
                        $path = $record->path . '.pub';
                        $content = '';

                        if (file_exists($path)) {
                            $content =
                                '<pre>' . htmlspecialchars(file_get_contents($path)) . '</pre>';
                        } else {
                            $content = "<div class='text-danger'>Public key file not found.</div>";
                        }

                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitActionLabel(false)
                    ->modalCancelActionLabel('Close'),

                TableAction::make('copy_to_server')
                    ->label('Copy to Server')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'This will copy the public key to the selected SSH server authorized_keys file.',
                    )
                    ->form([
                        Forms\Components\Select::make('ssh_config_id')
                            ->label('Target Server')
                            ->options(function () {
                                return \App\Models\SshConfig::all()->pluck('name', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (SshKey $record, array $data): void {
                        $sshConfig = \App\Models\SshConfig::find($data['ssh_config_id']);
                        $publicKeyPath = $record->path . '.pub';

                        if (!file_exists($publicKeyPath)) {
                            throw new \Exception('Public key file not found.');
                        }

                        $publicKey = trim(file_get_contents($publicKeyPath));

                        // Create SSH connection using Spatie SSH
                        $ssh = \Spatie\Ssh\Ssh::create(
                            $sshConfig->username,
                            $sshConfig->host,
                            $sshConfig->port,
                        );

                        // Use private key if available, otherwise use password
                        if (!empty($sshConfig->private_key_path)) {
                            $ssh->usePrivateKey($sshConfig->private_key_path);
                        } elseif (!empty($sshConfig->password)) {
                            $ssh->usePassword($sshConfig->password);
                        }

                        // Disable strict host key checking
                        $ssh->disableStrictHostKeyChecking();

                        // Execute the command to append the key to authorized_keys
                        $command = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$publicKey' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys";
                        $process = $ssh->execute($command);

                        if ($process->isSuccessful()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Key transferred successfully!')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception(
                                'Failed to transfer the key: ' . $process->getErrorOutput(),
                            );
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription(
                        'This will permanently delete the SSH key from both the database and the ~/.ssh directory.',
                    )
                    ->action(function (SshKey $record): void {
                        // Delete the actual key files
                        $keyPath = $record->path;
                        $pubKeyPath = $keyPath . '.pub';

                        if (file_exists($keyPath)) {
                            unlink($keyPath);
                        }

                        if (file_exists($pubKeyPath)) {
                            unlink($pubKeyPath);
                        }

                        // Delete the record
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('SSH key successfully deleted')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription(
                            'WARNING: This will permanently delete the selected SSH keys from both the database and the ~/.ssh directory.',
                        )
                        ->action(function (
                            \Illuminate\Database\Eloquent\Collection $records,
                        ): void {
                            foreach ($records as $record) {
                                // Delete the actual key files
                                $keyPath = $record->path;
                                $pubKeyPath = $keyPath . '.pub';

                                if (file_exists($keyPath)) {
                                    unlink($keyPath);
                                }

                                if (file_exists($pubKeyPath)) {
                                    unlink($pubKeyPath);
                                }
                            }

                            // Delete the records
                            $records->each->delete();

                            \Filament\Notifications\Notification::make()
                                ->title('SSH keys successfully deleted')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSshKeys::route('/'),
            'create' => Pages\CreateSshKey::route('/create'),
            'edit' => Pages\EditSshKey::route('/{record}/edit'),
        ];
    }
}
