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

    protected static ?string $navigationGroup = 'Income';

    protected static ?string $navigationLabel = 'Add-ons';

    protected static ?string $pluralModelLabel = 'Add-ons';

    protected static ?int $navigationSort = 900;

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
                        Forms\Components\Grid::make(3)
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
                                Forms\Components\Select::make('billing_day')
                                    ->label('Day')
                                    ->options(self::getDayOptions())
                                    ->required()
                                    ->default(now('Asia/Shanghai')->day),
                            ]),
                        Forms\Components\DatePicker::make('starts_on')
                            ->label('Effective from')
                            ->helperText('Applies every month from this date until you release it.')
                            ->default(now('Asia/Shanghai'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $date = Carbon::parse($state);
                                    $set('billing_year', $date->year);
                                    $set('billing_month', $date->month);
                                    $set('billing_day', $date->day);
                                }
                            }),
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
                                'active' => 'Active',
                                'released' => 'Released',
                            ])
                            ->colors([
                                'active' => 'success',
                                'released' => 'gray',
                            ])
                            ->icons([
                                'active' => 'heroicon-o-bolt',
                                'released' => 'heroicon-o-archive-box-x-mark',
                            ])
                            ->inline()
                            ->default('active')
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
                    ->label('Starts from')
                    ->getStateUsing(fn (BillingOtherItem $record) => Carbon::createFromDate($record->billing_year, $record->billing_month, $record->billing_day ?? 1, 'Asia/Shanghai')->format('M d, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'active' => 'success',
                        'released' => 'gray',
                    ])
                    ->icons([
                        'active' => 'heroicon-o-bolt',
                        'released' => 'heroicon-o-archive-box-x-mark',
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
                        'active' => 'Active',
                        'released' => 'Released',
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
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->color('gray')
                    ->visible(fn (BillingOtherItem $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (BillingOtherItem $record) {
                        $record->update([
                            'status' => 'released',
                            'released_at' => now('Asia/Shanghai'),
                            'released_by_user_id' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->color('success')
                    ->visible(fn (BillingOtherItem $record) => $record->status === 'released')
                    ->action(function (BillingOtherItem $record) {
                        $record->update([
                            'status' => 'active',
                            'released_at' => null,
                            'released_by_user_id' => null,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByDesc('billing_year')
                ->orderByDesc('billing_month')
                ->orderByDesc('billing_day'));
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

    protected static function mutateFormData(array $data): array
    {
        if (isset($data['billing_year'], $data['billing_month'], $data['billing_day'])) {
            $year = (int) $data['billing_year'];
            $month = (int) $data['billing_month'];
            $day = (int) $data['billing_day'];
            $daysInMonth = Carbon::create($year, $month, 1, 'Asia/Shanghai')->daysInMonth;
            $day = min($day, $daysInMonth);

            $date = Carbon::create(
                $year,
                $month,
                $day,
                0,
                0,
                0,
                'Asia/Shanghai'
            );

            $data['starts_on'] = $date->toDateString();
            $data['billing_day'] = $day;
        }

        return $data;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return self::mutateFormData($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return self::mutateFormData($data);
    }

    /**
     * @return array<int, string>
     */
    protected static function getYearOptions(): array
    {
        $current = now('Asia/Shanghai')->year;
        $years = [];
        for ($i = $current - 1; $i <= $current + 2; $i++) {
            $years[$i] = (string) $i;
        }

        return $years;
    }

    /**
     * @return array<int, string>
     */
    protected static function getMonthOptions(): array
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = sprintf('%02d', $i);
        }

        return $months;
    }

    /**
     * @return array<int, string>
     */
    protected static function getDayOptions(): array
    {
        $days = [];
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = sprintf('%02d', $i);
        }

        return $days;
    }
}
