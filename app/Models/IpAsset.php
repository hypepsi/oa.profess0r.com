<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'cidr',
        'ip_provider_id',
        'client_id',
        'location_id',
        'ipt_provider_id',
        'type',
        'asn',
        'status',
        'cost',
        'price',
        'notes',
    ];

    // 关联 IP Provider
    public function ipProvider()
    {
        return $this->belongsTo(Provider::class, 'ip_provider_id');
    }

    // 关联 Client
    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    // 关联 Location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // 关联 IPT Provider
    public function iptProvider()
    {
        return $this->belongsTo(IptProvider::class, 'ipt_provider_id');
    }
}
