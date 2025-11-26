<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_billing_payment_id',
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

    public function billingPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerBillingPayment::class, 'customer_billing_payment_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
