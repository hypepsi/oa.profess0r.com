<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
        'contact_wechat',
        'contact_email',
        'contact_telegram',
        'abuse_email',
        'active',
    ];

    // 关联IP资产
    public function ipAssets()
    {
        return $this->hasMany(IpAsset::class, 'client_id');
    }

    public function billingPayments()
    {
        return $this->hasMany(CustomerBillingPayment::class);
    }

    public function billingOtherItems()
    {
        return $this->hasMany(BillingOtherItem::class);
    }
}

