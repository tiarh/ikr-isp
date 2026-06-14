<?php

namespace App\Exports;

use App\Models\PsbOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PsbOrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return PsbOrder::query()->latest();
    }

    public function headings(): array
    {
        return [
            'Order #', 'Customer', 'Phone', 'Address', 'Village', 'District',
            'Package', 'Router', 'PPPoE User', 'OLT Type', 'Status',
            'Teknisi', 'Created', 'Provisioned', 'Synced',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->customer_name,
            $row->customer_phone,
            $row->customer_address,
            $row->village,
            $row->district,
            $row->package,
            $row->router_name,
            $row->pppoe_user,
            $row->olt_type?->value,
            $row->status->value,
            $row->teknisi->pluck('name')->join(', '),
            $row->created_at?->toDateTimeString(),
            $row->provisioned_at?->toDateTimeString(),
            $row->ebilling_synced_at?->toDateTimeString(),
        ];
    }
}
