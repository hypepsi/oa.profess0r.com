<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatacenterProvider extends Model
{
    protected $fillable = [
        'name',
        'location',
        'power',
        'address',
        'hosting_fee',
        'other_fee',
        'notes',
        'active',
    ];

    protected $casts = [
        'hosting_fee' => 'decimal:2',
        'other_fee' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * 获取月度总费用
     */
    public function getMonthlyTotalFeeAttribute(): float
    {
        return (float) (($this->hosting_fee ?? 0) + ($this->other_fee ?? 0));
    }
}
