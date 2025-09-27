<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'main_ip',
        'location_id',
        'provider_id',   // 注意：列名仍然叫 provider_id，但它指向 IptProvider
        'credentials',
    ];

    // 关联 Location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // 关联 IPT Provider（使用 devices.provider_id 外键）
    public function iptProvider()
    {
        return $this->belongsTo(IptProvider::class, 'provider_id');
    }
}
