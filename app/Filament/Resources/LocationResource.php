<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationGroup = 'Asset Management';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Locations';
    protected static ?string $pluralModelLabel = 'Locations';
    protected static ?string $modelLabel = 'Location';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'Colocation' => 'Colocation',
                    'Third-party' => 'Third-party',
                ])
                ->required(),

            Forms\Components\TextInput::make('address')
                ->label('Address')
                ->maxLength(255),

            Forms\Components\TextInput::make('country')
                ->label('Country')
                ->maxLength(64),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
