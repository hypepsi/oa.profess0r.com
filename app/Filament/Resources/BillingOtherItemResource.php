<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingOtherItemResource\Pages;
use App\Models\BillingOtherItem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillingOtherItemResource extends Resource
{
    protected static ?string $model = BillingOtherItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Other Items';

    protected static ?string $pluralModelLabel = 'Other Items';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Details')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Select customer'),
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->label('Category')
                            ->maxLength(255)
                            ->placeholder('Bandwidth / Hosting / Agency / ...'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('billing_year')
                                    ->label('Year')
                                    ->options(self::getYearOptions())
                                    ->required()
                                    ->default(now('Asia/Shanghai')->year),
                                Forms\Components\Select::make('billing_month')
                                    ->label('Month')
                                    ->options(self::getMonthOptions())
                                    ->required()
                                    ->default(now('Asia/Shanghai')->month),
                            ]),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\ToggleButtons::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                            ])
                            ->colors([
                                'pending' => 'warning',
                                'confirmed' => 'success',
                            ])
                            ->icons([
                                'pending' => 'heroicon-o-clock',
                                'confirmed' => 'heroicon-o-check',
                            ])
                            ->inline()
                            ->default('pending')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('billing_period')
                    ->label('Billing Month')
                    ->getStateUsing(fn (BillingOtherItem $record) => Carbon::createFromDate($record->billing_year, $record->billing_month, 1, 'Asia/Shanghai')->format('F Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'pending' => 'warning',
                        'confirmed' => 'success',
                    ])
                    ->icons([
                        'pending' => 'heroicon-o-clock',
                        'confirmed' => 'heroicon-o-check-circle',
                    ]),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                    ]),
                Tables\Filters\Filter::make('billing_period')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(self::getYearOptions()),
                        Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options(self::getMonthOptions()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['year'] ?? null, fn (Builder $q, $year) => $q->where('billing_year', $year))
                            ->when($data['month'] ?? null, fn (Builder $q, $month) => $q->where('billing_month', $month));
                    }),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('billing_year')->orderByDesc('billing_month'));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillingOtherItems::route('/'),
            'create' => Pages\CreateBillingOtherItem::route('/create'),
            'edit' => Pages\EditBillingOtherItem::route('/{record}/edit'),
        ];
    }

    protected static function getYearOptions(): array
    {
        $current = now('Asia/Shanghai')->year;
        $years = [];
        for ($i = $current - 1; $i <= $current + 2; $i++) {
            $years[$i] = (string) $i;
        }

        return $years;
    }

    protected static function getMonthOptions(): array
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = sprintf('%02d', $i);
        }

        return $months;
    }
}
