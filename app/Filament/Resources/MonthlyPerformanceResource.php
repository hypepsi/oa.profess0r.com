<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyPerformanceResource\Pages;
use App\Filament\Resources\MonthlyPerformanceResource\RelationManagers;
use App\Models\MonthlyPerformance;
use App\Services\PerformanceCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthlyPerformanceResource extends Resource
{
    protected static ?string $model = MonthlyPerformance::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Performance & Salary';
    
    protected static ?string $navigationGroup = 'Compensation';
    
    protected static ?int $navigationSort = 502;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Read Only - Auto Calculated')
                    ->description('This data is automatically calculated. Use the "Calculate" action to update.')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->disabled(),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('year')
                                    ->disabled(),
                                Forms\Components\TextInput::make('month')
                                    ->disabled(),
                            ]),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->badge()
                    ->color('primary')
                    ->sortable(['year', 'month']),
                
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('USD')
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('USD')
                    ->color('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Net Profit')
                    ->money('USD')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('workflow_deductions')
                    ->label('Deductions')
                    ->money('USD')
                    ->color('warning')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('total_compensation')
                    ->label('Total Salary')
                    ->money('USD')
                    ->color('warning')
                    ->weight('bold')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('calculated_at')
                    ->label('Calculated')
                    ->dateTime('m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('year')
                    ->options(function () {
                        $currentYear = now()->year;
                        return collect(range($currentYear - 2, $currentYear + 1))
                            ->mapWithKeys(fn ($year) => [$year => $year]);
                    }),
                
                Tables\Filters\SelectFilter::make('month')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))])),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Performance Details')
                    ->modalContent(fn (MonthlyPerformance $record) => view('filament.resources.monthly-performance.details', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalc')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (MonthlyPerformance $record) {
                        $calculator = new PerformanceCalculator();
                        $calculator->calculateMonthlyPerformance($record->employee_id, $record->year, $record->month);
                        
                        Notification::make()
                            ->success()
                            ->title('Performance Recalculated')
                            ->send();
                    }),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('calculate_current_month')
                    ->label('Calculate Current Month')
                    ->icon('heroicon-o-calculator')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {
                        $calculator = new PerformanceCalculator();
                        $results = $calculator->calculateAllEmployees(now()->year, now()->month);
                        
                        Notification::make()
                            ->success()
                            ->title('Calculated for ' . count($results) . ' employees')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc');
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
            'index' => Pages\ListMonthlyPerformances::route('/'),
        ];
    }
}
