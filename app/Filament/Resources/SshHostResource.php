<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshHostResource\Pages;
use App\Models\SshHost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshHostResource extends Resource
{
    protected static ?string $model = SshHost::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SSH Hosts';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignorable: fn ($record) => $record)
                    ->maxLength(255)
                    ->label('Host Name')
                    ->placeholder('Enter a unique name for this SSH host')
                    ->helperText('This will be used as the Host entry in your SSH config'),

                Forms\Components\TextInput::make('hostname')
                    ->required()
                    ->maxLength(255)
                    ->label('Hostname/IP')
                    ->placeholder('example.com or 192.168.1.100')
                    ->helperText('The hostname or IP address of the remote server'),

                Forms\Components\TextInput::make('port')
                    ->required()
                    ->numeric()
                    ->default(22)
                    ->minValue(1)
                    ->maxValue(65535)
                    ->label('Port')
                    ->placeholder('22')
                    ->helperText('The SSH port of the remote server'),

                Forms\Components\TextInput::make('user')
                    ->required()
                    ->default('root')
                    ->maxLength(255)
                    ->label('Username')
                    ->placeholder('root')
                    ->helperText('The username to use when connecting to the remote server'),

                Forms\Components\Select::make('identity_file')
                    ->relationship('sshKey', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->label('SSH Key')
                    ->placeholder('Select an SSH key')
                    ->helperText('The SSH key to use for authentication (optional)'),

                Forms\Components\Toggle::make('active')
                    ->required()
                    ->default(true)
                    ->label('Active')
                    ->helperText('Inactive hosts will not be included in your SSH config'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Host Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hostname')
                    ->label('Hostname/IP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('port')
                    ->label('Port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('identity_file')
                    ->label('SSH Key')
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('testConnection')
                        ->label('Test Connection')
                        ->icon('heroicon-o-wifi')
                        ->color('success')
                        ->action(function (SshHost $record, App\Services\SshService $sshService) {
                            $result = $sshService->executeCommand($record, 'echo "Connection successful"');

                            if ($result['success']) {
                                Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Connection Successful')
                                    ->body('Successfully connected to ' . $record->hostname)
                                    ->send();
                            } else {
                                Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Connection Failed')
                                    ->body($result['error'] ?: 'Failed to connect to ' . $record->hostname)
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('syncToConfig')
                        ->label('Sync to Config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (SshHost $record, App\Services\SshService $sshService) {
                            $configContent = $record->toSshConfigFormat();
                            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
                            $configPath = "{$homePath}/.ssh/config.d/{$record->name}";

                            try {
                                if (! is_dir(dirname($configPath))) {
                                    mkdir(dirname($configPath), 0700, true);
                                }

                                file_put_contents($configPath, $configContent);
                                chmod($configPath, 0600);

                                Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Config Synced')
                                    ->body('Host configuration has been saved to ' . $configPath)
                                    ->send();
                            } catch (\Exception $e) {
                                Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('syncAllToConfig')
                        ->label('Sync Selected to Config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, App\Services\SshService $sshService) {
                            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
                            $configDPath = "{$homePath}/.ssh/config.d";

                            try {
                                if (! is_dir($configDPath)) {
                                    mkdir($configDPath, 0700, true);
                                }

                                $syncCount = 0;

                                foreach ($records as $host) {
                                    $configContent = $host->toSshConfigFormat();
                                    $configPath = "{$configDPath}/{$host->name}";

                                    file_put_contents($configPath, $configContent);
                                    chmod($configPath, 0600);
                                    $syncCount++;
                                }

                                Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Configs Synced')
                                    ->body("Synced {$syncCount} host configurations to {$configDPath}")
                                    ->send();
                            } catch (\Exception $e) {
                                Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultPaginationPageOption(5);
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
            'index' => Pages\ListSshHosts::route('/'),
            'edit' => Pages\EditSshHost::route('/{record}/edit'),
        ];
    }
}
