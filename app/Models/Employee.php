<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'department', // sales / technical / owner
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if employee is owner/boss
     */
    public function isOwner(): bool
    {
        return strtolower($this->department) === 'owner';
    }

    /**
     * Check if employee is sales
     */
    public function isSales(): bool
    {
        return strtolower($this->department) === 'sales';
    }

    // 关联IP资产（作为销售人员）
    public function ipAssets()
    {
        return $this->hasMany(IpAsset::class, 'sales_person_id');
    }

    // 关联其他收入
    public function incomeOtherItems()
    {
        return $this->hasMany(IncomeOtherItem::class, 'sales_person_id');
    }

    // 关联薪酬配置
    public function compensation()
    {
        return $this->hasOne(EmployeeCompensation::class)->where('is_active', true);
    }

    // 关联月度业绩
    public function monthlyPerformances()
    {
        return $this->hasMany(MonthlyPerformance::class);
    }
}
