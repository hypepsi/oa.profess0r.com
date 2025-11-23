<?php

namespace App\Filament\Resources\IptProviderResource\Pages;

use App\Filament\Resources\IptProviderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use App\Filament\Resources\IptProviderResource\Widgets\IptProviderStats;

class ListIptProviders extends ListRecords
{
    protected static string $resource = IptProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New IPT Provider'),
        ];
    }

    // 在列表页顶部显示统计卡片
    protected function getHeaderWidgets(): array
    {
        return [
            IptProviderStats::class,
        ];
    }
}
