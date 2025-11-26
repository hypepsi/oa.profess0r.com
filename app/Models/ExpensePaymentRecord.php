<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpensePaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_expense_payment_id',
        'amount',
        'paid_at',
        'recorded_by_user_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function expensePayment(): BelongsTo
    {
        return $this->belongsTo(ProviderExpensePayment::class, 'provider_expense_payment_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
