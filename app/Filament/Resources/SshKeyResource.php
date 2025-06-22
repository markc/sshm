<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages\EditSshKey;
use App\Filament\Resources\SshKeyResource\Pages\ListSshKeys;
use App\Models\SshKey;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SshKeyResource extends Resource
{
    protected static ?string $model = SshKey::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'SSH Keys';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Basic Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(ignorable: fn ($record) => $record)
                                    ->maxLength(255)
                                    ->label('Key Name')
                                    ->placeholder('Enter a unique name for this SSH key')
                                    ->helperText('This will be used as the filename (e.g., id_ed25519)'),

                                TextInput::make('comment')
                                    ->maxLength(255)
                                    ->label('Comment')
                                    ->placeholder('username@hostname (optional)')
                                    ->helperText('A comment to help identify this key'),

                                Select::make('type')
                                    ->required()
                                    ->options([
                                        'ed25519' => 'ED25519 (Recommended)',
                                        'rsa' => 'RSA',
                                        'ecdsa' => 'ECDSA',
                                        'dsa' => 'DSA (Not Recommended)',
                                    ])
                                    ->default('ed25519')
                                    ->label('Key Type')
                                    ->helperText('ED25519 is recommended for new keys'),

                                Toggle::make('active')
                                    ->required()
                                    ->default(true)
                                    ->label('Active')
                                    ->helperText('Inactive keys will not be synced to the filesystem'),
                            ]),

                        Tab::make('Key Contents')
                            ->schema([
                                Textarea::make('public_key')
                                    ->required()
                                    ->label('Public Key')
                                    ->placeholder('ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...')
                                    ->helperText('The public key content (typically found in ~/.ssh/id_ed25519.pub)')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Textarea::make('private_key')
                                    ->required()
                                    ->label('Private Key')
                                    ->placeholder('-----BEGIN OPENSSH PRIVATE KEY-----...')
                                    ->helperText('The private key content (typically found in ~/.ssh/id_ed25519)')
                                    ->rows(10)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Key Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ed25519' => 'success',
                        'rsa' => 'warning',
                        'ecdsa' => 'info',
                        'dsa' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')
                    ->label('Comment')
                    ->getStateUsing(function (SshKey $record) {
                        return $record->getCommentFromPublicKey();
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('public_key')
                    ->label('Public Key')
                    ->searchable(false)
                    ->limit(40)
                    ->copyable()
                    ->copyMessage('Public key copied to clipboard')
                    ->copyMessageDuration(1500),

                TextColumn::make('fingerprint')
                    ->label('Fingerprint')
                    ->getStateUsing(function (SshKey $record) {
                        try {
                            return $record->getFingerprint();
                        } catch (Exception $e) {
                            return 'Error: ' . $e->getMessage();
                        }
                    })
                    ->searchable(false)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('type')
                    ->options([
                        'ed25519' => 'ED25519',
                        'rsa' => 'RSA',
                        'ecdsa' => 'ECDSA',
                        'dsa' => 'DSA',
                    ]),
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
                    Action::make('copyPublicKey')
                        ->label('Copy Public Key')
                        ->icon('heroicon-o-clipboard-document')
                        ->color('success')
                        ->action(function (SshKey $record) {
                            // This only sets up the UI for clipboard copy
                            // The actual copy is handled by JavaScript in the browser
                        })
                        ->extraAttributes([
                            'x-data' => '{ copied: false }',
                            'x-on:click' => "
                                navigator.clipboard.writeText('" . htmlspecialchars_decode(addslashes('{{ $record->public_key }}')) . "');
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            ",
                            'x-bind:class' => "copied ? 'bg-green-500' : ''",
                        ])
                        ->modalContent(fn (SshKey $record) => view('filament.resources.ssh-key-resource.copy-public-key', ['record' => $record])),
                    Action::make('syncToFile')
                        ->label('Sync to File')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (SshKey $record, SshService $sshService) {
                            $homePath = app(SshSettings::class)->getHomeDir();
                            $privateKeyPath = "{$homePath}/.ssh/{$record->name}";
                            $publicKeyPath = "{$homePath}/.ssh/{$record->name}.pub";

                            try {
                                if (! is_dir(dirname($privateKeyPath))) {
                                    mkdir(dirname($privateKeyPath), 0700, true);
                                }

                                file_put_contents($privateKeyPath, $record->private_key);
                                chmod($privateKeyPath, 0600);

                                file_put_contents($publicKeyPath, $record->public_key);
                                chmod($publicKeyPath, 0644);

                                Notification::make()
                                    ->success()
                                    ->title('Key Synced')
                                    ->body('SSH key has been saved to filesystem')
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
                    BulkAction::make('syncAllToFiles')
                        ->label('Sync Selected to Files')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (Collection $records, SshService $sshService) {
                            $homePath = app(SshSettings::class)->getHomeDir();
                            $sshPath = "{$homePath}/.ssh";

                            try {
                                if (! is_dir($sshPath)) {
                                    mkdir($sshPath, 0700, true);
                                }

                                $syncCount = 0;

                                foreach ($records as $key) {
                                    $privateKeyPath = "{$sshPath}/{$key->name}";
                                    $publicKeyPath = "{$sshPath}/{$key->name}.pub";

                                    file_put_contents($privateKeyPath, $key->private_key);
                                    chmod($privateKeyPath, 0600);

                                    file_put_contents($publicKeyPath, $key->public_key);
                                    chmod($publicKeyPath, 0644);

                                    $syncCount++;
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Keys Synced')
                                    ->body("Synced {$syncCount} SSH keys to filesystem")
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
            'index' => ListSshKeys::route('/'),
            'edit' => EditSshKey::route('/{record}/edit'),
        ];
    }
}
