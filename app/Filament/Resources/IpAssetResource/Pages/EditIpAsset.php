<?php

namespace App\Filament\Resources\IpAssetResource\Pages;

use App\Filament\Resources\IpAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIpAsset extends EditRecord
{
    protected static string $resource = IpAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 在编辑表单下方显示变更历史记录
     */
    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            IpAssetResource\Widgets\ChangeHistoryWidget::class,
        ];
    }
}
