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
        'is_paid',
        'paid_at',
        'recorded_by_user_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'actual_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
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

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query
            ->where('billing_year', $year)
            ->where('billing_month', $month);
    }
}
