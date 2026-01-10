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
    
    protected static ?string $navigationLabel = 'Compensation Config';
    
    protected static ?string $navigationGroup = 'Compensation';
    
    protected static ?int $navigationSort = 501;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Compensation Configuration')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Base Salary (USD)')
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
                            ->helperText('Enter percentage, e.g., 25 for 25%')
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => $state / 100)
                            ->formatStateUsing(fn ($state) => $state ? $state * 100 : 25),
                        
                        Forms\Components\Toggle::make('exclude_from_shared_cost')
                            ->label('Exclude from Shared Cost Allocation')
                            ->helperText('Enable for boss/owner who should not bear shared costs')
                            ->default(false),
                        
                        Forms\Components\DatePicker::make('effective_from')
                            ->label('Effective From'),
                        
                        Forms\Components\DatePicker::make('effective_to')
                            ->label('Effective To'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
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
                    ->label('Commission Rate')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('exclude_from_shared_cost')
                    ->label('Exclude Shared Cost')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('effective_from')
                    ->label('Effective From')
                    ->date()
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
