<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'ip_asset_revenue',
        'other_income',
        'total_revenue',
        'ip_direct_cost',
        'shared_cost',
        'shared_cost_ratio',
        'total_cost',
        'net_profit',
        'base_salary',
        'commission_rate',
        'commission_amount',
        'total_compensation',
        'active_subnet_count',
        'total_subnet_count',
        'active_customer_count',
        'calculation_details',
        'notes',
        'calculated_at',
        'calculated_by_user_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'ip_asset_revenue' => 'decimal:2',
        'other_income' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'ip_direct_cost' => 'decimal:2',
        'shared_cost' => 'decimal:2',
        'shared_cost_ratio' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'total_compensation' => 'decimal:2',
        'active_subnet_count' => 'integer',
        'total_subnet_count' => 'integer',
        'active_customer_count' => 'integer',
        'calculation_details' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by_user_id');
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%d-%02d', $this->year, $this->month);
    }
}
