<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeOtherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'customer_id',
        'manual_source',
        'date',
        'project',
        'cny_amount',
        'usd_amount',
        'exchange_rate',
        'payment_method',
        'sales_person_id',
        'evidence',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'cny_amount' => 'decimal:2',
        'usd_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_person_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * 获取来源名称
     */
    public function getSourceNameAttribute(): string
    {
        if ($this->source_type === 'customer' && $this->customer) {
            return $this->customer->name;
        }
        return $this->manual_source ?? 'Unknown';
    }
}
