<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\Widgets\ProviderStats;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    // 左边菜单显示的名字
    protected static ?string $navigationLabel = 'IP Providers';

    // 菜单分组
    protected static ?string $navigationGroup = 'Asset Management';

    // 页面标题显示
    protected static ?string $modelLabel = 'IP Provider';
    protected static ?string $pluralModelLabel = 'IP Providers';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('website')
                    ->url()
                    ->nullable(),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->nullable(),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('website')
                    ->url(fn ($record) => $record->website, true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ProviderStats::class,
        ];
    }
}
