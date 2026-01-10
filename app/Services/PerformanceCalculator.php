<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\MonthlyPerformance;
use App\Models\IpAsset;
use App\Models\IncomeOtherItem;
use App\Models\ProviderExpensePayment;
use App\Models\Workflow;
use Carbon\Carbon;

class PerformanceCalculator
{
    /**
     * 计算指定员工指定月份的业绩
     */
    public function calculateMonthlyPerformance(int $employeeId, int $year, int $month): MonthlyPerformance
    {
        $employee = Employee::findOrFail($employeeId);
        $compensation = $employee->compensation;
        
        // 获取或创建月度业绩记录
        $performance = MonthlyPerformance::firstOrNew([
            'employee_id' => $employeeId,
            'year' => $year,
            'month' => $month,
        ]);
        
        // 1. 计算收入
        $revenueData = $this->calculateRevenue($employeeId, $year, $month);
        
        // 2. 计算成本
        $costData = $this->calculateCost($employeeId, $year, $month);
        
        // 3. 计算workflow扣款
        $deductionData = $this->calculateWorkflowDeductions($employeeId, $year, $month);
        
        // 4. 计算利润
        $totalRevenue = $revenueData['total'];
        $totalCost = $costData['total'];
        $netProfit = $totalRevenue - $totalCost;
        
        // 5. 计算薪酬（利润 - 扣款）
        $baseSalary = $compensation ? $compensation->base_salary : 0;
        $commissionRate = $compensation ? $compensation->commission_rate : 0.25;
        $profitAfterDeductions = $netProfit - $deductionData['total'];
        $commissionAmount = $profitAfterDeductions > 0 ? $profitAfterDeductions * $commissionRate : 0;
        $totalCompensation = $baseSalary + $commissionAmount;
        
        // 6. 保存数据
        $performance->fill([
            'ip_asset_revenue' => $revenueData['ip_asset_revenue'],
            'other_income' => $revenueData['other_income'],
            'total_revenue' => $totalRevenue,
            
            'ip_direct_cost' => $costData['ip_direct_cost'],
            'shared_cost' => $costData['shared_cost'],
            'shared_cost_ratio' => $costData['shared_cost_ratio'],
            'total_cost' => $totalCost,
            
            'net_profit' => $netProfit,
            'workflow_deductions' => $deductionData['total'],
            'overdue_workflow_count' => $deductionData['count'],
            
            'base_salary' => $baseSalary,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'total_compensation' => $totalCompensation,
            
            'active_subnet_count' => $costData['active_subnet_count'],
            'total_subnet_count' => $costData['total_subnet_count'],
            'active_customer_count' => $revenueData['active_customer_count'],
            
            'calculation_details' => [
                'revenue' => $revenueData,
                'cost' => $costData,
                'deductions' => $deductionData,
                'calculated_at' => now('Asia/Shanghai')->toDateTimeString(),
            ],
            'calculated_at' => now('Asia/Shanghai'),
            'calculated_by_user_id' => auth()->id(),
        ]);
        
        $performance->save();
        
        return $performance;
    }
    
    /**
     * 计算收入
     */
    protected function calculateRevenue(int $employeeId, int $year, int $month): array
    {
        // 1. IP资产收入 (该销售的所有Active IP的price总和)
        $ipAssetRevenue = IpAsset::where('sales_person_id', $employeeId)
            ->where('status', 'Active')
            ->sum('price') ?? 0;
        
        // 2. 其他收入
        $otherIncome = IncomeOtherItem::where('sales_person_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('usd_amount') ?? 0;
        
        // 统计客户数
        $activeCustomerCount = IpAsset::where('sales_person_id', $employeeId)
            ->where('status', 'Active')
            ->distinct('client_id')
            ->count('client_id');
        
        return [
            'ip_asset_revenue' => (float) $ipAssetRevenue,
            'other_income' => (float) $otherIncome,
            'total' => (float) ($ipAssetRevenue + $otherIncome),
            'active_customer_count' => $activeCustomerCount,
        ];
    }
    
    /**
     * 计算成本
     */
    protected function calculateCost(int $employeeId, int $year, int $month): array
    {
        // 1. IP资产直接成本 (该销售的所有Active IP的cost总和)
        $ipDirectCost = IpAsset::where('sales_person_id', $employeeId)
            ->where('status', 'Active')
            ->sum('cost') ?? 0;
        
        // 2. 计算分摊成本（只计算Provider费用，不包括IP cost）
        $sharedCostData = $this->calculateSharedCost($employeeId, $year, $month);
        
        return [
            'ip_direct_cost' => (float) $ipDirectCost,
            'shared_cost' => $sharedCostData['amount'],
            'shared_cost_ratio' => $sharedCostData['ratio'],
            'total' => (float) ($ipDirectCost + $sharedCostData['amount']),
            'active_subnet_count' => $sharedCostData['employee_subnet_count'],
            'total_subnet_count' => $sharedCostData['total_subnet_count'],
        ];
    }
    
    /**
     * 计算分摊成本
     * 
     * 逻辑：
     * 1. 获取该月所有Provider费用（机房托管、IP Transit等混合成本）
     * 2. 统计全公司Active子网总数（排除Owner）
     * 3. 统计该员工的Active子网数
     * 4. 按子网数量比例分摊成本
     */
    protected function calculateSharedCost(int $employeeId, int $year, int $month): array
    {
        // 获取该月所有Provider费用总和
        $totalProviderExpense = ProviderExpensePayment::where('expense_year', $year)
            ->where('expense_month', $month)
            ->where('is_paid', true)
            ->sum('actual_amount') ?? 0;
        
        // 如果没有费用，返回0
        if ($totalProviderExpense == 0) {
            return [
                'amount' => 0,
                'ratio' => 0,
                'employee_subnet_count' => 0,
                'total_subnet_count' => 0,
            ];
        }
        
        // 统计该员工的Active子网数
        $employeeSubnetCount = IpAsset::where('sales_person_id', $employeeId)
            ->where('status', 'Active')
            ->count();
        
        // 统计全公司所有Active子网数（排除Owner部门的员工）
        $totalSubnetCount = IpAsset::where('status', 'Active')
            ->whereHas('salesPerson', function ($query) {
                $query->where('department', '!=', 'owner');
            })
            ->count();
        
        // 如果没有子网，返回0
        if ($totalSubnetCount == 0) {
            return [
                'amount' => 0,
                'ratio' => 0,
                'employee_subnet_count' => $employeeSubnetCount,
                'total_subnet_count' => 0,
            ];
        }
        
        // 计算分摊比例
        $ratio = $employeeSubnetCount / $totalSubnetCount;
        $sharedCost = $totalProviderExpense * $ratio;
        
        return [
            'amount' => (float) $sharedCost,
            'ratio' => (float) $ratio,
            'employee_subnet_count' => $employeeSubnetCount,
            'total_subnet_count' => $totalSubnetCount,
        ];
    }
    
    /**
     * 计算workflow扣款
     * 
     * 逻辑：统计该员工该月的overdue workflows
     */
    protected function calculateWorkflowDeductions(int $employeeId, int $year, int $month): array
    {
        // 找出该员工该月创建的且标记为overdue的workflows
        $overdueWorkflows = Workflow::whereHas('assignees', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->where('is_overdue', true)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
        
        $totalDeduction = $overdueWorkflows->sum('deduction_amount');
        
        return [
            'total' => (float) $totalDeduction,
            'count' => $overdueWorkflows->count(),
            'workflows' => $overdueWorkflows->map(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'title' => $workflow->title,
                    'deduction' => $workflow->deduction_amount,
                ];
            })->toArray(),
        ];
    }
    
    /**
     * 批量计算所有销售人员的月度业绩
     */
    public function calculateAllEmployees(int $year, int $month): array
    {
        // 只计算sales部门的员工，排除owner
        $salesEmployees = Employee::where('department', 'sales')
            ->where('is_active', true)
            ->get();
        
        $results = [];
        foreach ($salesEmployees as $employee) {
            $results[] = $this->calculateMonthlyPerformance($employee->id, $year, $month);
        }
        
        return $results;
    }
}
