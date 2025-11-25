<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingOtherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'title',
        'category',
        'billing_year',
        'billing_month',
        'billing_day',
        'starts_on',
        'amount',
        'description',
        'status',
        'released_at',
        'released_by_user_id',
        'recorded_by_user_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'billing_day' => 'integer',
        'starts_on' => 'date',
        'released_at' => 'datetime',
        'meta' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }

    public function effectiveStartDate(): \Carbon\Carbon
    {
        $year = $this->billing_year ?? now()->year;
        $month = $this->billing_month ?? now()->month;
        $day = $this->billing_day ?? 1;

        return \Carbon\Carbon::create($year, $month, $day, 0, 0, 0, 'Asia/Shanghai')->startOfMonth();
    }

    public function releaseStartDate(): ?\Carbon\Carbon
    {
        return $this->released_at
            ? $this->released_at->copy()->startOfMonth()
            : null;
    }
}
