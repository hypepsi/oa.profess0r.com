<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DatacenterProviderResource\Pages;
use App\Filament\Resources\DatacenterProviderResource\RelationManagers;
use App\Models\DatacenterProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatacenterProviderResource extends Resource
{
    protected static ?string $model = DatacenterProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Metadata';
    protected static ?int $navigationSort = 60;
    protected static ?string $navigationLabel = 'Datacenter Providers';
    protected static ?string $pluralModelLabel = 'Datacenter Providers';
    protected static ?string $modelLabel = 'Datacenter Provider';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255),

                Forms\Components\TextInput::make('power')
                    ->label('Power')
                    ->maxLength(255)
                    ->helperText('电力信息，如：100kW'),

                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->rows(3)
                    ->maxLength(65535),

                Forms\Components\TextInput::make('hosting_fee')
                    ->label('Hosting Fee (Monthly)')
                    ->numeric()
                    ->prefix('$')
                    ->helperText('托管费用（每月）'),

                Forms\Components\TextInput::make('other_fee')
                    ->label('Other Fee (Monthly)')
                    ->numeric()
                    ->prefix('$')
                    ->helperText('其他费用（每月）'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->maxLength(65535),

                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),
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

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable(),

                Tables\Columns\TextColumn::make('power')
                    ->label('Power')
                    ->searchable(),

                Tables\Columns\TextColumn::make('hosting_fee')
                    ->label('Hosting Fee')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('other_fee')
                    ->label('Other Fee')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_total_fee')
                    ->label('Monthly Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatacenterProviders::route('/'),
            'create' => Pages\CreateDatacenterProvider::route('/create'),
            'edit' => Pages\EditDatacenterProvider::route('/{record}/edit'),
        ];
    }
}
