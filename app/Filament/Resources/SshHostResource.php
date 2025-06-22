<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshHostResource\Pages\EditSshHost;
use App\Filament\Resources\SshHostResource\Pages\ListSshHosts;
use App\Models\SshHost;
use App\Services\SshService;
use App\Settings\SshSettings;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SshHostResource extends Resource
{
    protected static ?string $model = SshHost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SSH Hosts';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignorable: fn ($record) => $record)
                    ->maxLength(255)
                    ->label('Host Name')
                    ->placeholder('Enter a unique name for this SSH host')
                    ->helperText('This will be used as the Host entry in your SSH config'),

                TextInput::make('hostname')
                    ->required()
                    ->maxLength(255)
                    ->label('Hostname/IP')
                    ->placeholder('example.com or 192.168.1.100')
                    ->helperText('The hostname or IP address of the remote server'),

                TextInput::make('port')
                    ->required()
                    ->numeric()
                    ->default(22)
                    ->minValue(1)
                    ->maxValue(65535)
                    ->label('Port')
                    ->placeholder('22')
                    ->helperText('The SSH port of the remote server'),

                TextInput::make('user')
                    ->required()
                    ->default('root')
                    ->maxLength(255)
                    ->label('Username')
                    ->placeholder('root')
                    ->helperText('The username to use when connecting to the remote server'),

                Select::make('identity_file')
                    ->relationship('sshKey', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->label('SSH Key')
                    ->placeholder('Select an SSH key')
                    ->helperText('The SSH key to use for authentication (optional)'),

                Toggle::make('active')
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
                TextColumn::make('name')
                    ->label('Host Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('hostname')
                    ->label('Hostname/IP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('port')
                    ->label('Port')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identity_file')
                    ->label('SSH Key')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('testConnection')
                        ->label('Test Connection')
                        ->icon('heroicon-o-wifi')
                        ->color('success')
                        ->action(function (SshHost $record, SshService $sshService) {
                            $result = $sshService->executeCommand($record, 'echo "Connection successful"');

                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Connection Successful')
                                    ->body('Successfully connected to ' . $record->hostname)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Connection Failed')
                                    ->body($result['error'] ?: 'Failed to connect to ' . $record->hostname)
                                    ->send();
                            }
                        }),
                    Action::make('syncToConfig')
                        ->label('Sync to Config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (SshHost $record, SshService $sshService) {
                            $configContent = $record->toSshConfigFormat();
                            $homePath = app(SshSettings::class)->getHomeDir();
                            $configPath = "{$homePath}/.ssh/config.d/{$record->name}";

                            try {
                                if (! is_dir(dirname($configPath))) {
                                    mkdir(dirname($configPath), 0700, true);
                                }

                                file_put_contents($configPath, $configContent);
                                chmod($configPath, 0600);

                                Notification::make()
                                    ->success()
                                    ->title('Config Synced')
                                    ->body('Host configuration has been saved to ' . $configPath)
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('syncAllToConfig')
                        ->label('Sync Selected to Config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (Collection $records, SshService $sshService) {
                            $homePath = app(SshSettings::class)->getHomeDir();
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

                                Notification::make()
                                    ->success()
                                    ->title('Configs Synced')
                                    ->body("Synced {$syncCount} host configurations to {$configDPath}")
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
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
            'index' => ListSshHosts::route('/'),
            'edit' => EditSshHost::route('/{record}/edit'),
        ];
    }
}
