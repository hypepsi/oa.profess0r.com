<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeoFeedLocationResource\Pages;
use App\Models\GeoFeedLocation;
use App\Services\GeoFeedService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;

class GeoFeedLocationResource extends Resource
{
    protected static ?string $model = GeoFeedLocation::class;

    protected static ?string $slug = 'geofeed-locations';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'GeoFeed Locations';
    protected static ?string $pluralModelLabel = 'GeoFeed Locations';
    protected static ?string $modelLabel = 'GeoFeed Location';
    protected static ?string $navigationGroup = 'Metadata';
    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('country_code')
                            ->label('Country Code')
                            ->maxLength(2)
                            ->required()
                            ->helperText('ISO 3166-1 alpha-2, e.g., US, HK, GB')
                            ->placeholder('US'),
                        Forms\Components\TextInput::make('region')
                            ->label('Region / State / Province')
                            ->maxLength(255)
                            ->helperText('State or province code/name')
                            ->placeholder('CA, NY, Texas'),
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(255)
                            ->helperText('City name')
                            ->placeholder('Los Angeles, Hong Kong, London'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Postal Code')
                            ->maxLength(50)
                            ->helperText('Optional postal/zip code')
                            ->placeholder('90001'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('GeoFeed')
                    ->getStateUsing(fn (GeoFeedLocation $record) => $record->label),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('region')
                    ->label('Region')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->searchable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->label('Postal')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('country_code')
            ->searchable()
            ->persistSearchInSession();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeoFeedLocations::route('/'),
            'create' => Pages\CreateGeoFeedLocation::route('/create'),
            'edit' => Pages\EditGeoFeedLocation::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['country_code'] = strtoupper(trim((string) ($data['country_code'] ?? '')));
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['country_code'] = strtoupper(trim((string) ($data['country_code'] ?? '')));
        return $data;
    }
}
