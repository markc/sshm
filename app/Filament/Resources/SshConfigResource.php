<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshConfigResource\Pages;
use App\Models\SshConfig;
use App\Models\SshKey;
use App\Services\SshManagerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;

class SshConfigResource extends Resource
{
    protected static ?string $model = SshConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SSH Connections';

    protected static ?string $navigationGroup = 'SSH Management';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Server Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Connection Name'),
                    Forms\Components\TextInput::make('host')
                        ->required()
                        ->maxLength(255)
                        ->label('Hostname/IP'),
                    Forms\Components\TextInput::make('port')->required()->numeric()->default(22),
                    Forms\Components\TextInput::make('username')->required()->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Authentication')
                ->schema([
                    Forms\Components\Select::make('private_key_path')
                        ->label('SSH Key')
                        ->options(function () {
                            $sshManager = new SshManagerService();
                            $sshDir = $sshManager->getSshDir();

                            // Get all SSH keys from the database
                            $keys = SshKey::all();

                            $options = [];
                            foreach ($keys as $key) {
                                $options[$key->path] = $key->name . ' (' . $key->comment . ')';
                            }

                            return $options;
                        })
                        ->searchable()
                        ->allowHtml()
                        ->helperText('Leave empty to use password authentication'),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->helperText('Required if private key is not provided'),
                    Forms\Components\Toggle::make('is_default')
                        ->label('Set as default connection')
                        ->helperText('Only one connection can be default'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('host')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('port')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('private_key_path')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Edit'),

                TableAction::make('save_to_config')
                    ->label('Save to Config File')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Save to Config File')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'This will create or update the corresponding SSH config file in ~/.ssh/config.d/',
                    )
                    ->action(function (SshConfig $record): void {
                        try {
                            $sshManager = new SshManagerService();

                            // Ensure SSH directory is initialized
                            $sshManager->initializeSshDirectory();

                            // Create or update the host entry
                            $sshManager->createHost(
                                $record->name,
                                $record->host,
                                $record->port,
                                $record->username,
                                $record->private_key_path,
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Configuration saved')
                                ->body(
                                    "SSH configuration for '{$record->name}' has been saved to the config file.",
                                )
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to save configuration')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                TableAction::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->iconButton()
                    ->tooltip('Test Connection')
                    ->action(function (SshConfig $record): void {
                        try {
                            // Create SSH connection using Spatie SSH
                            $ssh = \Spatie\Ssh\Ssh::create(
                                $record->username,
                                $record->host,
                                $record->port,
                            );

                            // Use private key if available, otherwise use password
                            if (!empty($record->private_key_path)) {
                                $ssh->usePrivateKey($record->private_key_path);
                            } elseif (!empty($record->password)) {
                                $ssh->usePassword($record->password);
                            }

                            // Disable strict host key checking for the test
                            $ssh->disableStrictHostKeyChecking();

                            // Execute a simple command to test the connection
                            $process = $ssh->execute('echo "Connection successful!"');

                            if ($process->isSuccessful()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Connection successful')
                                    ->body('Successfully connected to ' . $record->host)
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception(
                                    'Connection test failed: ' . $process->getErrorOutput(),
                                );
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'This will delete the connection from the database. The actual SSH config file in ~/.ssh/config.d/ will not be deleted.',
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
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
            'index' => Pages\ListSshConfigs::route('/'),
            'create' => Pages\CreateSshConfig::route('/create'),
            'edit' => Pages\EditSshConfig::route('/{record}/edit'),
        ];
    }
}
