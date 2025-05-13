<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshConfigResource\Pages;
use App\Models\SshConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshConfigResource extends Resource
{
    protected static ?string $model = SshConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SSH Configurations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                        Forms\Components\TextInput::make('port')
                            ->required()
                            ->numeric()
                            ->default(22),
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Authentication')
                    ->schema([
                        Forms\Components\TextInput::make('private_key_path')
                            ->maxLength(255)
                            ->helperText('Leave empty to use password authentication'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->helperText('Required if private key is not provided'),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Set as default connection')
                            ->helperText('Only one connection can be default'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('host')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port'),
                Tables\Columns\TextColumn::make('username'),
                Tables\Columns\IconColumn::make('is_default')
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSshConfigs::route('/'),
            'create' => Pages\CreateSshConfig::route('/create'),
            'edit' => Pages\EditSshConfig::route('/{record}/edit'),
        ];
    }
}
