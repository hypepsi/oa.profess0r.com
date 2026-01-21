<?php

namespace App\Filament\Resources\IpAssetResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class ChangeHistoryWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.resources.ip-asset-resource.widgets.change-history';

    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    /**
     * 删除客户转换历史记录（仅管理员可用）- Filament Action
     */
    public function deleteClientHistoryAction(): Action
    {
        return Action::make('deleteClientHistory')
            ->requiresConfirmation()
            ->modalHeading('Delete Client Transfer Record')
            ->modalDescription('Are you sure you want to delete this client transfer history record? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->color('danger')
            ->action(function (array $arguments) {
                $index = $arguments['index'] ?? null;
                
                if ($index === null) {
                    return;
                }

                // 检查是否为管理员
                if (auth()->user()->email !== 'admin@bunnycommunications.com') {
                    Notification::make()
                        ->danger()
                        ->title('Permission Denied')
                        ->body('Only administrators can delete client transfer history.')
                        ->send();
                    return;
                }

                $meta = $this->record->meta ?? [];
                $clientHistory = $meta['client_history'] ?? [];

                // 删除指定索引的记录
                if (isset($clientHistory[$index])) {
                    unset($clientHistory[$index]);
                    // 重新索引数组
                    $clientHistory = array_values($clientHistory);
                    
                    $meta['client_history'] = $clientHistory;
                    
                    // 如果历史记录为空，清空 client_changed_at
                    if (empty($clientHistory)) {
                        $this->record->client_changed_at = null;
                    }
                    
                    $this->record->meta = $meta;
                    $this->record->save();

                    Notification::make()
                        ->success()
                        ->title('Deleted')
                        ->body('Client transfer history record has been deleted.')
                        ->send();
                }
            });
    }

    /**
     * 删除成本变更历史记录（仅管理员可用）- Filament Action
     */
    public function deleteCostHistoryAction(): Action
    {
        return Action::make('deleteCostHistory')
            ->requiresConfirmation()
            ->modalHeading('Delete Cost Change Record')
            ->modalDescription('Are you sure you want to delete this cost change history record? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->color('danger')
            ->action(function (array $arguments) {
                $index = $arguments['index'] ?? null;
                
                if ($index === null) {
                    return;
                }

                // 检查是否为管理员
                if (auth()->user()->email !== 'admin@bunnycommunications.com') {
                    Notification::make()
                        ->danger()
                        ->title('Permission Denied')
                        ->body('Only administrators can delete cost change history.')
                        ->send();
                    return;
                }

                $meta = $this->record->meta ?? [];
                $costHistory = $meta['cost_history'] ?? [];

                if (isset($costHistory[$index])) {
                    unset($costHistory[$index]);
                    $costHistory = array_values($costHistory);
                    
                    $meta['cost_history'] = $costHistory;
                    
                    if (empty($costHistory)) {
                        $this->record->cost_changed_at = null;
                    }
                    
                    $this->record->meta = $meta;
                    $this->record->save();

                    Notification::make()
                        ->success()
                        ->title('Deleted')
                        ->body('Cost change history record has been deleted.')
                        ->send();
                }
            });
    }

    /**
     * 删除价格变更历史记录（仅管理员可用）- Filament Action
     */
    public function deletePriceHistoryAction(): Action
    {
        return Action::make('deletePriceHistory')
            ->requiresConfirmation()
            ->modalHeading('Delete Price Change Record')
            ->modalDescription('Are you sure you want to delete this price change history record? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->color('danger')
            ->action(function (array $arguments) {
                $index = $arguments['index'] ?? null;
                
                if ($index === null) {
                    return;
                }

                // 检查是否为管理员
                if (auth()->user()->email !== 'admin@bunnycommunications.com') {
                    Notification::make()
                        ->danger()
                        ->title('Permission Denied')
                        ->body('Only administrators can delete price change history.')
                        ->send();
                    return;
                }

                $meta = $this->record->meta ?? [];
                $priceHistory = $meta['price_history'] ?? [];

                if (isset($priceHistory[$index])) {
                    unset($priceHistory[$index]);
                    $priceHistory = array_values($priceHistory);
                    
                    $meta['price_history'] = $priceHistory;
                    
                    if (empty($priceHistory)) {
                        $this->record->price_changed_at = null;
                    }
                    
                    $this->record->meta = $meta;
                    $this->record->save();

                    Notification::make()
                        ->success()
                        ->title('Deleted')
                        ->body('Price change history record has been deleted.')
                        ->send();
                }
            });
    }

    /**
     * Generic delete history action for any field
     */
    public function deleteFieldHistoryAction(): Action
    {
        return Action::make('deleteFieldHistory')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Delete ' . ucfirst($arguments['field'] ?? 'Field') . ' Change Record')
            ->modalDescription('Are you sure you want to delete this change history record? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->color('danger')
            ->action(function (array $arguments) {
                $index = $arguments['index'] ?? null;
                $field = $arguments['field'] ?? null;
                
                if ($index === null || $field === null) {
                    return;
                }

                // 检查是否为管理员
                if (auth()->user()->email !== 'admin@bunnycommunications.com') {
                    Notification::make()
                        ->danger()
                        ->title('Permission Denied')
                        ->body('Only administrators can delete change history.')
                        ->send();
                    return;
                }

                $meta = $this->record->meta ?? [];
                $historyKey = $field . '_history';
                $history = $meta[$historyKey] ?? [];

                if (isset($history[$index])) {
                    unset($history[$index]);
                    $history = array_values($history);
                    
                    $meta[$historyKey] = $history;
                    $this->record->meta = $meta;
                    $this->record->save();

                    Notification::make()
                        ->success()
                        ->title('Deleted')
                        ->body(ucfirst($field) . ' change history record has been deleted.')
                        ->send();
                }
            });
    }
}

