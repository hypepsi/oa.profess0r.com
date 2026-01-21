<?php

namespace App\Observers;

use App\Models\IpAsset;
use App\Models\Customer;
use App\Models\Location;
use Carbon\Carbon;

class IpAssetObserver
{
    /**
     * Handle the IpAsset "updating" event.
     * Track all field changes with history
     */
    public function updating(IpAsset $ipAsset): void
    {
        $meta = $ipAsset->meta ?? [];
        $now = Carbon::now('Asia/Shanghai');
        
        // Track Status changes
        if ($ipAsset->isDirty('status')) {
            $this->trackChange($meta, 'status', [
                'from' => $ipAsset->getOriginal('status'),
                'to' => $ipAsset->status,
                'changed_at' => $now->toDateTimeString(),
            ]);
            
            // Special handling for Released status
            if ($ipAsset->status === 'Released') {
                $ipAsset->released_at = $now;
            } elseif ($ipAsset->getOriginal('status') === 'Released') {
                $ipAsset->released_at = null;
            }
        }
        
        // Track Type changes
        if ($ipAsset->isDirty('type')) {
            $this->trackChange($meta, 'type', [
                'from' => $ipAsset->getOriginal('type'),
                'to' => $ipAsset->type,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track ASN changes
        if ($ipAsset->isDirty('asn')) {
            $this->trackChange($meta, 'asn', [
                'from' => $ipAsset->getOriginal('asn'),
                'to' => $ipAsset->asn,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track Client changes
        if ($ipAsset->isDirty('client_id')) {
            $ipAsset->client_changed_at = $now;
            $this->trackChange($meta, 'client', [
                'from_id' => $ipAsset->getOriginal('client_id'),
                'to_id' => $ipAsset->client_id,
                'from_name' => $ipAsset->getOriginal('client_id') ? Customer::find($ipAsset->getOriginal('client_id'))?->name : null,
                'to_name' => $ipAsset->client_id ? Customer::find($ipAsset->client_id)?->name : null,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track Location changes
        if ($ipAsset->isDirty('location_id')) {
            $this->trackChange($meta, 'location', [
                'from_id' => $ipAsset->getOriginal('location_id'),
                'to_id' => $ipAsset->location_id,
                'from_name' => $ipAsset->getOriginal('location_id') ? Location::find($ipAsset->getOriginal('location_id'))?->name : null,
                'to_name' => $ipAsset->location_id ? Location::find($ipAsset->location_id)?->name : null,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track Geo Location changes
        if ($ipAsset->isDirty('geo_location')) {
            $this->trackChange($meta, 'geo_location', [
                'from' => $ipAsset->getOriginal('geo_location'),
                'to' => $ipAsset->geo_location,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track Cost changes
        if ($ipAsset->isDirty('cost')) {
            $ipAsset->cost_changed_at = $now;
            $this->trackChange($meta, 'cost', [
                'from' => $ipAsset->getOriginal('cost'),
                'to' => $ipAsset->cost,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        // Track Price changes
        if ($ipAsset->isDirty('price')) {
            $ipAsset->price_changed_at = $now;
            $this->trackChange($meta, 'price', [
                'from' => $ipAsset->getOriginal('price'),
                'to' => $ipAsset->price,
                'changed_at' => $now->toDateTimeString(),
            ]);
        }
        
        $ipAsset->meta = $meta;
    }
    
    /**
     * Track a field change in meta history
     */
    private function trackChange(array &$meta, string $field, array $changeData): void
    {
        $historyKey = $field . '_history';
        
        if (!isset($meta[$historyKey])) {
            $meta[$historyKey] = [];
        }
        
        $meta[$historyKey][] = $changeData;
    }
}

