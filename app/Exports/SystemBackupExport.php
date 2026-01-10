<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\IpAsset;
use App\Models\Workflow;
use App\Models\Document;
use App\Models\CustomerBillingPayment;
use App\Models\ProviderExpensePayment;
use App\Models\IncomeOtherItem;
use App\Models\BillingOtherItem;
use App\Models\Employee;
use App\Models\Provider;
use App\Models\IptProvider;
use App\Models\DatacenterProvider;
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SystemBackupExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Summary' => new SummarySheet(),
            'Customers' => new CustomersSheet(),
            'IP Assets' => new IpAssetsSheet(),
            'Workflows' => new WorkflowsSheet(),
            'Documents' => new DocumentsSheet(),
            'Billing Payments' => new BillingPaymentsSheet(),
            'Expense Payments' => new ExpensePaymentsSheet(),
            'Income Other' => new IncomeOtherSheet(),
            'Billing Add-ons' => new BillingAddonsSheet(),
            'Employees' => new EmployeesSheet(),
            'IP Providers' => new ProvidersSheet(),
            'IPT Providers' => new IptProvidersSheet(),
            'Datacenter Providers' => new DatacenterProvidersSheet(),
            'Devices' => new DevicesSheet(),
            'Locations' => new LocationsSheet(),
            'Users' => new UsersSheet(),
        ];
    }
}

// Summary Sheet
class SummarySheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles
{
    public function collection()
    {
        return collect([
            ['Backup Time', now('Asia/Shanghai')->format('Y-m-d H:i:s')],
            ['System', 'Bunny Communications OA'],
            ['', ''],
            ['Data Statistics', ''],
            ['Customers', Customer::count()],
            ['IP Assets', IpAsset::count()],
            ['Workflows', Workflow::count()],
            ['Documents', Document::count()],
            ['Employees', Employee::count()],
            ['Providers', Provider::count() + IptProvider::count() + DatacenterProvider::count()],
            ['Devices', Device::count()],
            ['Users', User::count()],
        ]);
    }

    public function headings(): array
    {
        return ['Item', 'Value'];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}

// Customers Sheet
class CustomersSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Customer::all()->map(fn($c) => [
            'ID' => $c->id,
            'Name' => $c->name,
            'Website' => $c->website,
            'Contact WeChat' => $c->contact_wechat,
            'Contact Email' => $c->contact_email,
            'Contact Telegram' => $c->contact_telegram,
            'Abuse Email' => $c->abuse_email,
            'Active' => $c->active ? 'Yes' : 'No',
            'Created At' => $c->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Website', 'Contact WeChat', 'Contact Email', 'Contact Telegram', 'Abuse Email', 'Active', 'Created At'];
    }
}

// IP Assets Sheet
class IpAssetsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return IpAsset::with(['ipProvider', 'client', 'salesPerson', 'location', 'iptProvider'])->get()->map(fn($ip) => [
            'ID' => $ip->id,
            'CIDR' => $ip->cidr,
            'IP Provider' => $ip->ipProvider?->name ?? '—',
            'Client' => $ip->client?->name ?? '—',
            'Sales Person' => $ip->salesPerson?->name ?? '—',
            'Location' => $ip->location?->name ?? '—',
            'Geo Location' => $ip->geo_location ?? '—',
            'IPT Provider' => $ip->iptProvider?->name ?? '—',
            'Type' => $ip->type ?? '—',
            'ASN' => $ip->asn ?? '—',
            'Status' => $ip->status ?? '—',
            'Cost' => $ip->cost ? '$' . number_format($ip->cost, 2) : '—',
            'Price' => $ip->price ? '$' . number_format($ip->price, 2) : '—',
            'Notes' => $ip->notes ?? '—',
            'Created At' => $ip->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'CIDR', 'IP Provider', 'Client', 'Sales Person', 'Location', 'Geo Location', 'IPT Provider', 'Type', 'ASN', 'Status', 'Cost', 'Price', 'Notes', 'Created At'];
    }
}

// Workflows Sheet
class WorkflowsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Workflow::with(['client', 'assignees'])->get()->map(fn($w) => [
            'ID' => $w->id,
            'Title' => $w->title,
            'Client' => $w->client?->name ?? '—',
            'Priority' => $w->priority ?? '—',
            'Status' => $w->status ?? '—',
            'Assignees' => $w->assignees->pluck('name')->join(', ') ?: '—',
            'Description' => $w->description ?? '—',
            'Due Date' => $w->due_at?->format('Y-m-d'),
            'Created At' => $w->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Title', 'Client', 'Priority', 'Status', 'Assignees', 'Description', 'Due Date', 'Created At'];
    }
}

// Documents Sheet
class DocumentsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Document::with('uploadedBy')->get()->map(fn($d) => [
            'ID' => $d->id,
            'Title' => $d->title,
            'Category' => $d->category ?? '—',
            'File Name' => $d->file_name,
            'File Size' => $d->formatted_file_size,
            'Uploaded By' => $d->uploadedBy?->name ?? '—',
            'Description' => $d->description ?? '—',
            'Created At' => $d->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Title', 'Category', 'File Name', 'File Size', 'Uploaded By', 'Description', 'Created At'];
    }
}

// Billing Payments Sheet
class BillingPaymentsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return CustomerBillingPayment::with('customer')->get()->map(fn($p) => [
            'ID' => $p->id,
            'Customer' => $p->customer?->name ?? '—',
            'Period' => $p->billing_year . '-' . str_pad($p->billing_month, 2, '0', STR_PAD_LEFT),
            'Actual Amount' => $p->actual_amount ? '$' . number_format($p->actual_amount, 2) : '—',
            'Invoiced Amount' => $p->invoiced_amount ? '$' . number_format($p->invoiced_amount, 2) : '—',
            'Is Paid' => $p->is_paid ? 'Yes' : 'No',
            'Is Waived' => $p->is_waived ? 'Yes' : 'No',
            'Paid At' => $p->paid_at?->format('Y-m-d H:i:s') ?? '—',
            'Notes' => $p->notes ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Customer', 'Period', 'Actual Amount', 'Invoiced Amount', 'Is Paid', 'Is Waived', 'Paid At', 'Notes'];
    }
}

// Expense Payments Sheet
class ExpensePaymentsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return ProviderExpensePayment::with('provider')->get()->map(fn($p) => [
            'ID' => $p->id,
            'Provider' => $p->provider?->name ?? '—',
            'Provider Type' => class_basename($p->provider_type),
            'Period' => $p->expense_year . '-' . str_pad($p->expense_month, 2, '0', STR_PAD_LEFT),
            'Expected Amount' => $p->expected_amount ? '$' . number_format($p->expected_amount, 2) : '—',
            'Actual Amount' => $p->actual_amount ? '$' . number_format($p->actual_amount, 2) : '—',
            'Invoiced Amount' => $p->invoiced_amount ? '$' . number_format($p->invoiced_amount, 2) : '—',
            'Is Paid' => $p->is_paid ? 'Yes' : 'No',
            'Is Waived' => $p->is_waived ? 'Yes' : 'No',
            'Paid At' => $p->paid_at?->format('Y-m-d H:i:s') ?? '—',
            'Notes' => $p->notes ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Provider', 'Provider Type', 'Period', 'Expected Amount', 'Actual Amount', 'Invoiced Amount', 'Is Paid', 'Is Waived', 'Paid At', 'Notes'];
    }
}

// Income Other Sheet
class IncomeOtherSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return IncomeOtherItem::with(['customer', 'salesPerson'])->get()->map(fn($i) => [
            'ID' => $i->id,
            'Source Type' => $i->source_type,
            'Customer' => $i->customer?->name ?? $i->manual_source ?? '—',
            'Date' => $i->date?->format('Y-m-d'),
            'Project' => $i->project ?? '—',
            'CNY Amount' => $i->cny_amount ? '¥' . number_format($i->cny_amount, 2) : '—',
            'USD Amount' => $i->usd_amount ? '$' . number_format($i->usd_amount, 2) : '—',
            'Exchange Rate' => $i->exchange_rate ?? '—',
            'Payment Method' => $i->payment_method ?? '—',
            'Sales Person' => $i->salesPerson?->name ?? '—',
            'Notes' => $i->notes ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Source Type', 'Customer/Source', 'Date', 'Project', 'CNY Amount', 'USD Amount', 'Exchange Rate', 'Payment Method', 'Sales Person', 'Notes'];
    }
}

// Billing Add-ons Sheet
class BillingAddonsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return BillingOtherItem::with('customer')->get()->map(fn($b) => [
            'ID' => $b->id,
            'Customer' => $b->customer?->name ?? '—',
            'Title' => $b->title,
            'Category' => $b->category ?? '—',
            'Billing Period' => $b->billing_year . '-' . str_pad($b->billing_month, 2, '0', STR_PAD_LEFT),
            'Amount' => '$' . number_format($b->amount, 2),
            'Status' => $b->status ?? '—',
            'Starts On' => $b->starts_on?->format('Y-m-d') ?? '—',
            'Released At' => $b->released_at?->format('Y-m-d H:i:s') ?? '—',
            'Description' => $b->description ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Customer', 'Title', 'Category', 'Billing Period', 'Amount', 'Status', 'Starts On', 'Released At', 'Description'];
    }
}

// Employees Sheet
class EmployeesSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Employee::all()->map(fn($e) => [
            'ID' => $e->id,
            'Name' => $e->name,
            'Department' => $e->department ?? '—',
            'Position' => $e->position ?? '—',
            'Is Active' => $e->is_active ? 'Yes' : 'No',
            'Created At' => $e->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Department', 'Position', 'Is Active', 'Created At'];
    }
}

// Providers Sheet
class ProvidersSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Provider::all()->map(fn($p) => [
            'ID' => $p->id,
            'Name' => $p->name,
            'Website' => $p->website ?? '—',
            'Contact Email' => $p->contact_email ?? '—',
            'Active' => $p->active ? 'Yes' : 'No',
            'Created At' => $p->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Website', 'Contact Email', 'Active', 'Created At'];
    }
}

// IPT Providers Sheet
class IptProvidersSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return IptProvider::all()->map(fn($p) => [
            'ID' => $p->id,
            'Name' => $p->name,
            'Created At' => $p->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Created At'];
    }
}

// Datacenter Providers Sheet
class DatacenterProvidersSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return DatacenterProvider::all()->map(fn($d) => [
            'ID' => $d->id,
            'Name' => $d->name,
            'Location' => $d->location ?? '—',
            'Monthly Total Fee' => $d->monthly_total_fee ? '$' . number_format($d->monthly_total_fee, 2) : '—',
            'Active' => $d->active ? 'Yes' : 'No',
            'Created At' => $d->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Location', 'Monthly Total Fee', 'Active', 'Created At'];
    }
}

// Devices Sheet
class DevicesSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Device::with(['location', 'iptProvider'])->get()->map(fn($d) => [
            'ID' => $d->id,
            'Name' => $d->name,
            'Type' => $d->type ?? '—',
            'Main IP' => $d->main_ip ?? '—',
            'Location' => $d->location?->name ?? '—',
            'IPT Provider' => $d->iptProvider?->name ?? '—',
            'Created At' => $d->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Type', 'Main IP', 'Location', 'IPT Provider', 'Created At'];
    }
}

// Locations Sheet
class LocationsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return Location::all()->map(fn($l) => [
            'ID' => $l->id,
            'Name' => $l->name,
            'Created At' => $l->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Created At'];
    }
}

// Users Sheet
class UsersSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        return User::all()->map(fn($u) => [
            'ID' => $u->id,
            'Name' => $u->name,
            'Email' => $u->email,
            'Created At' => $u->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Created At'];
    }
}
