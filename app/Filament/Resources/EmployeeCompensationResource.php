<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCompensationResource\Pages;
use App\Filament\Resources\EmployeeCompensationResource\RelationManagers;
use App\Models\EmployeeCompensation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeCompensationResource extends Resource
{
    protected static ?string $model = EmployeeCompensation::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationLabel = 'Salary Settings';
    
    protected static ?string $pluralModelLabel = 'Salary Settings';
    
    protected static ?string $modelLabel = 'Salary Setting';
    
    protected static ?string $navigationGroup = 'Salary';
    
    protected static ?int $navigationSort = 501;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Salary Configuration')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name', fn ($query) => $query->where('department', 'sales'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Only sales employees need salary settings')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Base Salary (USD/Month)')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->required(),
                        
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->default(25)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Enter number, e.g., 25 means 25%')
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? $state / 100 : 0.25)
                            ->formatStateUsing(fn ($state) => is_numeric($state) && $state <= 1 ? $state * 100 : ($state ?: 25)),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base Salary')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('employee.name');
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
            'index' => Pages\ListEmployeeCompensation::route('/'),
            'create' => Pages\CreateEmployeeCompensation::route('/create'),
            'edit' => Pages\EditEmployeeCompensation::route('/{record}/edit'),
        ];
    }
}
