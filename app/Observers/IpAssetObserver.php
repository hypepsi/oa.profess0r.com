<?php

namespace App\Observers;

use App\Models\IpAsset;
use Carbon\Carbon;

class IpAssetObserver
{
    /**
     * Handle the IpAsset "updating" event.
     * 在更新前检查哪些字段变更了，自动记录时间和修改前后的值
     */
    public function updating(IpAsset $ipAsset): void
    {
        // 初始化 meta 数组用于存储历史变更
        $meta = $ipAsset->meta ?? [];
        
        if (!isset($meta['change_history'])) {
            $meta['change_history'] = [];
        }
        
        // 检查 status 是否变更为 Released
        if ($ipAsset->isDirty('status') && $ipAsset->status === 'Released') {
            $ipAsset->released_at = Carbon::now('Asia/Shanghai');
            $meta['released_from'] = $ipAsset->getOriginal('status');
        }
        
        // 如果 status 从 Released 改回其他状态，清空 released_at
        if ($ipAsset->isDirty('status') && $ipAsset->getOriginal('status') === 'Released' && $ipAsset->status !== 'Released') {
            $ipAsset->released_at = null;
        }
        
        // 检查 client_id 是否变更
        if ($ipAsset->isDirty('client_id')) {
            $oldClientId = $ipAsset->getOriginal('client_id');
            $newClientId = $ipAsset->client_id;
            
            $ipAsset->client_changed_at = Carbon::now('Asia/Shanghai');
            
            // 保存客户变更历史
            $meta['client_history'][] = [
                'from_id' => $oldClientId,
                'to_id' => $newClientId,
                'changed_at' => Carbon::now('Asia/Shanghai')->toDateTimeString(),
            ];
        }
        
        // 检查 cost 是否变更
        if ($ipAsset->isDirty('cost')) {
            $oldCost = $ipAsset->getOriginal('cost');
            $newCost = $ipAsset->cost;
            
            $ipAsset->cost_changed_at = Carbon::now('Asia/Shanghai');
            
            // 保存成本变更历史
            $meta['cost_history'][] = [
                'from' => $oldCost,
                'to' => $newCost,
                'changed_at' => Carbon::now('Asia/Shanghai')->toDateTimeString(),
            ];
        }
        
        // 检查 price 是否变更
        if ($ipAsset->isDirty('price')) {
            $oldPrice = $ipAsset->getOriginal('price');
            $newPrice = $ipAsset->price;
            
            $ipAsset->price_changed_at = Carbon::now('Asia/Shanghai');
            
            // 保存价格变更历史
            $meta['price_history'][] = [
                'from' => $oldPrice,
                'to' => $newPrice,
                'changed_at' => Carbon::now('Asia/Shanghai')->toDateTimeString(),
            ];
        }
        
        // 保存 meta 数据
        $ipAsset->meta = $meta;
    }
}

