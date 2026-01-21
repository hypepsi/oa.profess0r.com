<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IptProviderResource\Pages;
use App\Models\IptProvider;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\IptProviderResource\Widgets\IptProviderStats;

class IptProviderResource extends Resource
{
    protected static ?string $model = IptProvider::class;

    protected static ?string $navigationIcon  = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'IPT Providers';
    protected static ?string $pluralModelLabel = 'IPT Providers';
    protected static ?string $modelLabel = 'IPT Provider';
    protected static ?string $navigationGroup = 'Metadata';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Provider Name')
                ->required()
                ->maxLength(255),

            Select::make('bandwidth')
                ->label('Bandwidth')
                ->options(IptProvider::BANDWIDTH_OPTIONS)
                ->default('1G')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Provider Name')->searchable()->sortable(),
                TextColumn::make('bandwidth')->label('Bandwidth'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIptProviders::route('/'),
            'create' => Pages\CreateIptProvider::route('/create'),
            'edit'   => Pages\EditIptProvider::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            IptProviderStats::class,
        ];
    }
}
