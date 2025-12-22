<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BillingPaymentRecord;
use App\Models\CustomerBillingPayment;
use App\Models\ExpensePaymentRecord;
use App\Models\ProviderExpensePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class BillingActivityLogger
{
    public static function logPaymentRecorded(BillingPaymentRecord $record, CustomerBillingPayment $payment, $customer): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payment_recorded',
            'model_type' => CustomerBillingPayment::class,
            'model_id' => $payment->id,
            'description' => "Recorded payment of \${$record->amount} for {$customer->name} ({$payment->billing_year}-{$payment->billing_month})",
            'properties' => [
                'amount' => $record->amount,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'billing_year' => $payment->billing_year,
                'billing_month' => $payment->billing_month,
                'notes' => $record->notes,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logInvoiceUpdated(CustomerBillingPayment $payment, $customer, float $oldAmount = null, float $newAmount = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'invoice_updated',
            'model_type' => CustomerBillingPayment::class,
            'model_id' => $payment->id,
            'description' => "Updated invoiced amount for {$customer->name} ({$payment->billing_year}-{$payment->billing_month})",
            'properties' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'billing_year' => $payment->billing_year,
                'billing_month' => $payment->billing_month,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logPaymentWaived(CustomerBillingPayment $payment, $customer, float $amount, bool $isFull = false): void
    {
        $type = $isFull ? 'Full' : 'Partial';
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payment_waived',
            'model_type' => CustomerBillingPayment::class,
            'model_id' => $payment->id,
            'description' => "{$type} waived \${$amount} for {$customer->name} ({$payment->billing_year}-{$payment->billing_month})",
            'properties' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'billing_year' => $payment->billing_year,
                'billing_month' => $payment->billing_month,
                'waive_amount' => $amount,
                'is_full_waive' => $isFull,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logPaymentReset(CustomerBillingPayment $payment, $customer): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payment_reset',
            'model_type' => CustomerBillingPayment::class,
            'model_id' => $payment->id,
            'description' => "Reset payment for {$customer->name} ({$payment->billing_year}-{$payment->billing_month})",
            'properties' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'billing_year' => $payment->billing_year,
                'billing_month' => $payment->billing_month,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    // Expense logging methods
    public static function logExpensePaymentRecorded(ExpensePaymentRecord $record, ProviderExpensePayment $payment, $provider, string $providerType): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_payment_recorded',
            'model_type' => ProviderExpensePayment::class,
            'model_id' => $payment->id,
            'description' => "Recorded expense payment of \${$record->amount} for {$provider->name} ({$payment->expense_year}-{$payment->expense_month})",
            'properties' => [
                'amount' => $record->amount,
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'provider_type' => $providerType,
                'expense_year' => $payment->expense_year,
                'expense_month' => $payment->expense_month,
                'notes' => $record->notes,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logExpenseInvoiceUpdated(ProviderExpensePayment $payment, $provider, float $oldAmount = null, float $newAmount = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_invoice_updated',
            'model_type' => ProviderExpensePayment::class,
            'model_id' => $payment->id,
            'description' => "Updated expense invoiced amount for {$provider->name} ({$payment->expense_year}-{$payment->expense_month})",
            'properties' => [
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'expense_year' => $payment->expense_year,
                'expense_month' => $payment->expense_month,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logExpenseWaived(ProviderExpensePayment $payment, $provider, float $amount, bool $isFull = false): void
    {
        $type = $isFull ? 'Full' : 'Partial';
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_waived',
            'model_type' => ProviderExpensePayment::class,
            'model_id' => $payment->id,
            'description' => "{$type} waived expense \${$amount} for {$provider->name} ({$payment->expense_year}-{$payment->expense_month})",
            'properties' => [
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'expense_year' => $payment->expense_year,
                'expense_month' => $payment->expense_month,
                'waive_amount' => $amount,
                'is_full_waive' => $isFull,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logExpenseReset(ProviderExpensePayment $payment, $provider): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'expense_reset',
            'model_type' => ProviderExpensePayment::class,
            'model_id' => $payment->id,
            'description' => "Reset expense payment for {$provider->name} ({$payment->expense_year}-{$payment->expense_month})",
            'properties' => [
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'expense_year' => $payment->expense_year,
                'expense_month' => $payment->expense_month,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}



