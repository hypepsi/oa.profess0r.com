<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IpAssetResource\Pages;
use App\Models\IpAsset;
use App\Models\Provider;
use App\Models\Customer;
use App\Models\Location;
use App\Models\IptProvider;
use App\Models\Employee;
use App\Models\GeoFeedLocation;
use App\Services\GeoFeedService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class IpAssetResource extends Resource
{
    protected static ?string $model = IpAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'IP Assets';
    protected static ?string $pluralModelLabel = 'IP Assets';
    protected static ?string $modelLabel = 'IP Asset';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('cidr')
                ->label('CIDR')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'BGP' => 'BGP',
                    'ISP ASN' => 'ISP ASN',
                ]),

            Forms\Components\TextInput::make('asn')
                ->label('ASN')
                ->numeric(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'Active' => 'Active',
                    'Reserved' => 'Reserved',
                    'Released' => 'Released',
                ])
                ->default('Active'),

            Forms\Components\Select::make('ip_provider_id')
                ->label('IP Provider')
                ->options(Provider::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Select::make('client_id')
                ->label('Client')
                ->options(Customer::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Select::make('sales_person_id')
                ->label('Sales Person')
                ->options(function () {
                    return Employee::where('is_active', true)
                        ->whereIn('department', ['sales', 'owner'])
                        ->orderByRaw("FIELD(department, 'owner', 'sales')")
                        ->get()
                        ->mapWithKeys(fn ($employee) => [
                            $employee->id => $employee->name . ($employee->department === 'owner' ? ' (Owner)' : '')
                        ]);
                })
                ->searchable()
                ->placeholder('Select sales person'),

            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(Location::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Select::make('geo_location')
                ->label('Geo Location')
                ->searchable()
                ->preload()
                ->options(function () {
                    return GeoFeedLocation::query()
                        ->orderBy('country_code')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (GeoFeedLocation $location) => [$location->label => $location->label])
                        ->toArray();
                })
                ->getSearchResultsUsing(function (string $search) {
                    return GeoFeedLocation::query()
                        ->where('country_code', 'like', '%' . $search . '%')
                        ->orWhere('region', 'like', '%' . $search . '%')
                        ->orWhere('city', 'like', '%' . $search . '%')
                        ->orWhere('postal_code', 'like', '%' . $search . '%')
                        ->orderBy('country_code')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (GeoFeedLocation $location) => [$location->label => $location->label])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value) => $value)
                ->placeholder('Select GeoFeed location'),

            Forms\Components\Select::make('ipt_provider_id')
                ->label('IPT Provider')
                ->options(IptProvider::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\TextInput::make('cost')
                ->label('Cost')
                ->numeric()
                ->prefix('$'),

            Forms\Components\TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('$'),

            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->maxLength(65535),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid(['md' => 1]) // 让表格横向撑满，避免出现滚动条
            ->headerActions([
                Action::make('syncGeoFeed')
                    ->label('Sync GeoFeed')
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Sync GeoFeed to Remote')
                    ->modalDescription('Generate GeoFeed CSV from current IP assets and upload to remote server.')
                    ->modalSubmitActionLabel('Sync Now')
                    ->action(function () {
                        $service = app(GeoFeedService::class);
                        $result = $service->uploadTestFeed('geofeed.test.csv'); // 写死test.csv

                        if ($result['uploaded'] ?? false) {
                            Notification::make()
                                ->success()
                                ->title('GeoFeed Synced')
                                ->body('Successfully uploaded to remote server.')
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->warning()
                            ->title('Sync Failed')
                            ->body($result['message'] ?? 'Upload failed. Check configuration.')
                            ->send();
                    }),

                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->color('primary')
                    ->action(function () {
                        $records = IpAsset::with(['ipProvider', 'client', 'salesPerson', 'location', 'iptProvider'])->get();

                        $csvData = [];
                        $csvData[] = [
                            'CIDR', 'IP Provider', 'Client', 'Sales Person', 'Location', 'IPT Provider',
                            'Type', 'ASN', 'Status', 'Cost', 'Price', 'Notes', 'Created At'
                        ];

                        foreach ($records as $record) {
                            $csvData[] = [
                                $record->cidr,
                                optional($record->ipProvider)->name,
                                optional($record->client)->name,
                                optional($record->salesPerson)->name,
                                optional($record->location)->name,
                                optional($record->iptProvider)->name,
                                $record->type,
                                $record->asn,
                                $record->status,
                                $record->cost,
                                $record->price,
                                $record->notes,
                                $record->created_at,
                            ];
                        }

                        $filename = 'ip_assets_export_' . now()->format('Ymd_His') . '.csv';
                        $handle = fopen('php://temp', 'r+');
                        foreach ($csvData as $row) {
                            fputcsv($handle, $row);
                        }
                        rewind($handle);
                        $csv = stream_get_contents($handle);
                        fclose($handle);

                        return Response::streamDownload(function () use ($csv) {
                            echo $csv;
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),

                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->color('primary')
                    ->action(function () {
                        $records = IpAsset::with(['ipProvider', 'client', 'salesPerson', 'location', 'iptProvider'])->get();

                        $data = $records->map(function ($record) {
                            return [
                                'CIDR' => $record->cidr,
                                'IP Provider' => optional($record->ipProvider)->name,
                                'Client' => optional($record->client)->name,
                                'Sales Person' => optional($record->salesPerson)->name,
                                'Location' => optional($record->location)->name,
                                'IPT Provider' => optional($record->iptProvider)->name,
                                'Type' => $record->type,
                                'ASN' => $record->asn,
                                'Status' => $record->status,
                                'Cost' => $record->cost,
                                'Price' => $record->price,
                                'Notes' => $record->notes,
                                'Created At' => $record->created_at,
                            ];
                        });

                        $filename = 'ip_assets_export_' . now()->format('Ymd_His') . '.xlsx';

                        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection {
                            protected $data;
                            public function __construct($data) { $this->data = $data; }
                            public function collection() { return collect($this->data); }
                        }, $filename);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('cidr')
                    ->label('CIDR')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // 输入是合法 IPv4 地址时，用子网包含计算检索所属 CIDR
                        if (filter_var($search, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            return $query->whereRaw(
                                "LOCATE('/', cidr) > 0
                                 AND INET_ATON(?) BETWEEN
                                     INET_ATON(SUBSTRING_INDEX(cidr, '/', 1))
                                     AND (INET_ATON(SUBSTRING_INDEX(cidr, '/', 1))
                                          + POW(2, 32 - CAST(SUBSTRING_INDEX(cidr, '/', -1) AS UNSIGNED)) - 1)",
                                [$search]
                            );
                        }
                        // 其他情况（CIDR 前缀、关键词）保持原 LIKE 搜索
                        return $query->where('cidr', 'like', "%{$search}%");
                    })
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->copyMessage('Copied!'),
                Tables\Columns\TextColumn::make('ipProvider.name')->label('IP Provider')->searchable(),
                Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('salesPerson.name')
                    ->label('Sales Person')
                    ->searchable()
                    ->getStateUsing(function (IpAsset $record) {
                        return $record->salesPerson?->name ?? '—';
                    }),
                Tables\Columns\TextColumn::make('location.name')->label('Location')->searchable(),
                Tables\Columns\TextColumn::make('geo_location')
                    ->label('Geo Location')
                    ->searchable()
                    ->getStateUsing(function (IpAsset $record) {
                        if (!$record->geo_location) {
                            return '—';
                        }
                        $parts = explode(',', $record->geo_location);
                        return trim($parts[0] ?? $record->geo_location);
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('iptProvider.name')->label('IPT Provider')->searchable(),
                Tables\Columns\TextColumn::make('asn')
                    ->label('ASN')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Reserved' => 'warning',
                        'Released' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('geofeed_sync')
                    ->label('GeoFeed Sync')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Synced' => 'success',
                        'Different' => 'warning',
                        'Missing' => 'gray',
                        'Remote Unavailable' => 'danger',
                        'Unsupported' => 'gray',
                        default => 'gray',
                    })
                    ->getStateUsing(function (IpAsset $record) {
                        $service = app(GeoFeedService::class);
                        $match = $service->getRemoteMatchForCidr($record->cidr);
                        if (!$match) {
                            return 'Remote Unavailable';
                        }

                        $status = $match['status'] ?? '';
                        if ($status === 'unsupported') {
                            return 'Unsupported';
                        }
                        if ($status === 'unavailable') {
                            return 'Remote Unavailable';
                        }
                        if ($status === 'missing') {
                            return 'Missing';
                        }

                        $local = $service->buildLocalGeoFields($record->geo_location);
                        $remote = [
                            'country_code' => $match['country_code'] ?? '',
                            'region' => $match['region'] ?? '',
                            'city' => $match['city'] ?? '',
                            'postal_code' => $match['postal_code'] ?? '',
                        ];

                        return $service->isGeoSynced($local, $remote) ? 'Synced' : 'Different';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Cost')
                    ->fontFamily(FontFamily::Mono)
                    ->prefix('$')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->fontFamily(FontFamily::Mono)
                    ->prefix('$')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->date('Y-m-d')
                    ->sortable()
                    ->tooltip(fn (IpAsset $record) => $record->created_at?->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListIpAssets::route('/'),
            'create' => Pages\CreateIpAsset::route('/create'),
            'edit' => Pages\EditIpAsset::route('/{record}/edit'),
        ];
    }
}
