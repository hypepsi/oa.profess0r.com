<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class ProviderExpensePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_type',
        'provider_id',
        'expense_year',
        'expense_month',
        'expected_amount',
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
        'expense_year' => 'integer',
        'expense_month' => 'integer',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'invoiced_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_waived' => 'boolean',
        'paid_at' => 'datetime',
        'waived_at' => 'datetime',
        'meta' => 'array',
    ];

    public function provider(): MorphTo
    {
        return $this->morphTo();
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
        return $this->hasMany(ExpensePaymentRecord::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->paymentRecords()->sum('amount');
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query
            ->where('expense_year', $year)
            ->where('expense_month', $month);
    }
}
