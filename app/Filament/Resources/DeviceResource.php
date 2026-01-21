<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\Location;
use App\Models\IptProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationGroup = 'Metadata';
    protected static ?int $navigationSort = 40;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Devices';
    protected static ?string $pluralModelLabel = 'Devices';
    protected static ?string $modelLabel = 'Device';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Device Name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type')
                ->label('Device Type')
                ->options([
                    'Router' => 'Router',
                    'Switch' => 'Switch',
                    'Firewall' => 'Firewall',
                    'Server' => 'Server',
                    'Other' => 'Other',
                ])
                ->required(),

            Forms\Components\TextInput::make('main_ip')
                ->label('Main IP Address')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(Location::all()->pluck('name', 'id'))
                ->searchable(),

            // 这里改为读取 IptProvider
            Forms\Components\Select::make('provider_id')
                ->label('IPT Provider')
                ->options(IptProvider::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Textarea::make('credentials')
                ->label('Credentials')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Device Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('main_ip')
                    ->label('Main IP')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location'),

                // 表格列也改为 Ipt Provider 的名称
                Tables\Columns\TextColumn::make('iptProvider.name')
                    ->label('IPT Provider'),

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
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
