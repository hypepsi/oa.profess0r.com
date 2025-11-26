<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IpAssetResource\Pages;
use App\Models\IpAsset;
use App\Models\Provider;
use App\Models\Customer;
use App\Models\Location;
use App\Models\IptProvider;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
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
                ->options(Employee::where('department', 'sales')->where('is_active', true)->get()->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Select sales person'),

            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(Location::all()->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\Select::make('ipt_provider_id')
                ->label('IPT Provider')
                ->options(IptProvider::all()->pluck('name', 'id'))
                ->searchable(),

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
                            /** @var array<int, array<string, mixed>> */
                            protected array $data;
                            
                            /** @param array<int, array<string, mixed>> $data */
                            public function __construct(array $data) { 
                                $this->data = $data; 
                            }
                            
                            public function collection(): \Illuminate\Support\Collection { 
                                return collect($this->data); 
                            }
                        }, $filename);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('cidr')->label('CIDR')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('ipProvider.name')->label('IP Provider')->searchable(),
                Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('salesPerson.name')->label('Sales Person')->searchable(),
                Tables\Columns\TextColumn::make('location.name')->label('Location')->searchable(),
                Tables\Columns\TextColumn::make('iptProvider.name')->label('IPT Provider')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Type')->searchable(),
                Tables\Columns\TextColumn::make('asn')->label('ASN')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->searchable(),
                Tables\Columns\TextColumn::make('cost')->label('Cost'),
                Tables\Columns\TextColumn::make('price')->label('Price'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->date('Y-m-d')
                    ->sortable()
                    ->tooltip(fn (IpAsset $record) => $record->created_at?->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s')),
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
