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
        'amount',
        'description',
        'status',
        'recorded_by_user_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_year' => 'integer',
        'billing_month' => 'integer',
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
}
