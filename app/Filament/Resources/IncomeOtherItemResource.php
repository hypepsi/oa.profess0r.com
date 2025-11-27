<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeOtherItemResource\Pages;
use App\Models\IncomeOtherItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class IncomeOtherItemResource extends Resource
{
    protected static ?string $model = IncomeOtherItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Income';

    protected static ?string $navigationLabel = 'Other Income';

    protected static ?string $pluralModelLabel = 'Other Income';

    protected static ?int $navigationSort = 800;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Source Information')
                    ->schema([
                        Forms\Components\ToggleButtons::make('source_type')
                            ->label('Source Type')
                            ->options([
                                'customer' => 'Customer',
                                'manual' => 'Manual Input',
                            ])
                            ->colors([
                                'customer' => 'primary',
                                'manual' => 'gray',
                            ])
                            ->icons([
                                'customer' => 'heroicon-o-user-group',
                                'manual' => 'heroicon-o-pencil',
                            ])
                            ->inline()
                            ->default('customer')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('source_type') === 'customer')
                            ->required(fn (Forms\Get $get) => $get('source_type') === 'customer'),
                        Forms\Components\TextInput::make('manual_source')
                            ->label('Manual Source')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('source_type') === 'manual')
                            ->required(fn (Forms\Get $get) => $get('source_type') === 'manual')
                            ->placeholder('Enter source name'),
                    ]),
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now('Asia/Shanghai'))
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                        Forms\Components\TextInput::make('project')
                            ->label('Project')
                            ->maxLength(255)
                            ->placeholder('Project name'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cny_amount')
                                    ->label('Amount (CNY)')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state && is_numeric($state) && (float) $state > 0) {
                                            // 使用已有汇率（如果存在），否则获取新汇率
                                            $rate = $get('exchange_rate') ?: self::getExchangeRate();
                                            if ($rate) {
                                                $usdAmount = (float) $state / $rate;
                                                $set('usd_amount', round($usdAmount, 2));
                                                // 只有在没有汇率时才设置汇率（创建时）
                                                if (!$get('exchange_rate')) {
                                                    $set('exchange_rate', $rate);
                                                }
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('usd_amount')
                                    ->label('Amount (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state && is_numeric($state) && (float) $state > 0) {
                                            $cnyAmount = (float) $get('cny_amount');
                                            if ($cnyAmount > 0) {
                                                // 如果两个金额都有，计算汇率（仅在没有汇率时，即创建时）
                                                if (!$get('exchange_rate')) {
                                                    $rate = $cnyAmount / (float) $state;
                                                    $set('exchange_rate', round($rate, 4));
                                                }
                                            } else {
                                                // 如果只有USD，使用已有汇率或默认汇率计算CNY
                                                $rate = $get('exchange_rate') ?: self::getExchangeRate();
                                                if ($rate) {
                                                    $cnyAmount = (float) $state * $rate;
                                                    $set('cny_amount', round($cnyAmount, 2));
                                                    if (!$get('exchange_rate')) {
                                                        $set('exchange_rate', $rate);
                                                    }
                                                }
                                            }
                                        }
                                    }),
                            ]),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-calculated on create, locked after save'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'paypal' => 'PayPal',
                                'stripe' => 'Stripe',
                                'alipay' => 'Alipay',
                                'wechat_pay' => 'WeChat Pay',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->placeholder('Select payment method'),
                        Forms\Components\Select::make('sales_person_id')
                            ->label('Sales Person')
                            ->relationship('salesPerson', 'name', fn (Builder $query) => $query->where('department', 'sales')->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select sales person'),
                    ]),
                Forms\Components\Section::make('Evidence')
                    ->schema([
                        Forms\Components\FileUpload::make('evidence')
                            ->label('Evidence (Screenshot)')
                            ->required()
                            ->image()
                            ->imageEditor()
                            ->directory('income-evidence')
                            ->maxSize(10240) // 10MB
                            ->helperText('Upload screenshot evidence (required)'),
                    ]),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(1000)
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_name')
                    ->label('Source')
                    ->getStateUsing(fn (IncomeOtherItem $record) => $record->source_name)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project')
                    ->label('Project')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('cny_amount')
                    ->label('CNY Amount')
                    ->money('CNY', locale: 'zh_CN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usd_amount')
                    ->label('USD Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('salesPerson.name')
                    ->label('Sales Person')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('evidence')
                    ->label('Evidence')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Source Type')
                    ->options([
                        'customer' => 'Customer',
                        'manual' => 'Manual Input',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),
                Tables\Filters\SelectFilter::make('sales_person_id')
                    ->label('Sales Person')
                    ->relationship('salesPerson', 'name'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Date From'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Date To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['date_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date));
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
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeOtherItems::route('/'),
            'create' => Pages\CreateIncomeOtherItem::route('/create'),
            'edit' => Pages\EditIncomeOtherItem::route('/{record}/edit'),
        ];
    }

    /**
     * 获取汇率（可以从API获取或使用固定值）
     */
    protected static function getExchangeRate(): ?float
    {
        // 可以在这里集成汇率API，例如：
        // try {
        //     $response = Http::get('https://api.exchangerate-api.com/v4/latest/USD');
        //     $data = $response->json();
        //     return $data['rates']['CNY'] ?? null;
        // } catch (\Exception $e) {
        //     return null;
        // }
        
        // 暂时返回一个默认值，实际使用时应该从API获取
        return 7.2; // 示例汇率
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        return $data;
    }
}

