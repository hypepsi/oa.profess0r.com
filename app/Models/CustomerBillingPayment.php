<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CustomerBillingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'billing_year',
        'billing_month',
        'actual_amount',
        'invoiced_amount',
        'is_paid',
        'is_waived',
        'paid_at',
        'waived_at',
        'recorded_by_user_id',
        'waived_by_user_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'actual_amount' => 'decimal:2',
        'invoiced_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_waived' => 'boolean',
        'paid_at' => 'datetime',
        'waived_at' => 'datetime',
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

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by_user_id');
    }

    public function paymentRecords()
    {
        return $this->hasMany(BillingPaymentRecord::class);
    }

    public function getTotalReceivedAttribute(): float
    {
        return (float) $this->paymentRecords()->sum('amount');
    }


    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query
            ->where('billing_year', $year)
            ->where('billing_month', $month);
    }
}
