<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages;
use App\Models\SshKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshKeyResource extends Resource
{
    protected static ?string $model = SshKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'SSH Keys';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->unique(ignorable: fn ($record) => $record)
                                    ->maxLength(255)
                                    ->label('Key Name')
                                    ->placeholder('Enter a unique name for this SSH key')
                                    ->helperText('This will be used as the filename (e.g., id_ed25519)'),

                                Forms\Components\TextInput::make('comment')
                                    ->maxLength(255)
                                    ->label('Comment')
                                    ->placeholder('username@hostname (optional)')
                                    ->helperText('A comment to help identify this key'),

                                Forms\Components\Select::make('type')
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

                                Forms\Components\Toggle::make('active')
                                    ->required()
                                    ->default(true)
                                    ->label('Active')
                                    ->helperText('Inactive keys will not be synced to the filesystem'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Key Contents')
                            ->schema([
                                Forms\Components\Textarea::make('public_key')
                                    ->required()
                                    ->label('Public Key')
                                    ->placeholder('ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...')
                                    ->helperText('The public key content (typically found in ~/.ssh/id_ed25519.pub)')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('private_key')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Key Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
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
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->getStateUsing(function (SshKey $record) {
                        return $record->getCommentFromPublicKey();
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('public_key')
                    ->label('Public Key')
                    ->searchable(false)
                    ->limit(40)
                    ->tooltip(function (SshKey $record): string {
                        return $record->public_key;
                    })
                    ->copyable()
                    ->copyMessage('Public key copied to clipboard')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('fingerprint')
                    ->label('Fingerprint')
                    ->getStateUsing(function (SshKey $record) {
                        try {
                            return $record->getFingerprint();
                        } catch (\Exception $e) {
                            return 'Error: ' . $e->getMessage();
                        }
                    })
                    ->searchable(false)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'ed25519' => 'ED25519',
                        'rsa' => 'RSA',
                        'ecdsa' => 'ECDSA',
                        'dsa' => 'DSA',
                    ]),
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
                    Tables\Actions\Action::make('copyPublicKey')
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
                    Tables\Actions\Action::make('syncToFile')
                        ->label('Sync to File')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (SshKey $record, App\Services\SshService $sshService) {
                            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
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

                                Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Key Synced')
                                    ->body('SSH key has been saved to filesystem')
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
                    Tables\Actions\BulkAction::make('syncAllToFiles')
                        ->label('Sync Selected to Files')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, App\Services\SshService $sshService) {
                            $homePath = app(\App\Settings\SshSettings::class)->getHomeDir();
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

                                Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Keys Synced')
                                    ->body("Synced {$syncCount} SSH keys to filesystem")
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
