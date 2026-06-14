<?php

namespace App\Filament\Widgets;

use App\Enums\PsbStatus;
use App\Models\PsbOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PsbStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Order', PsbOrder::count())
                ->description('Semua PSB order')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Selesai', PsbOrder::where('status', PsbStatus::Done)->count())
                ->description('Order yang sudah sync ke eBilling')
                ->color('success'),
            Stat::make('In Progress', PsbOrder::whereIn('status', [
                PsbStatus::Submitted, PsbStatus::CoverageOk,
                PsbStatus::Assigned, PsbStatus::Provisioning, PsbStatus::Photos,
            ])->count())
                ->description('Pipeline aktif')
                ->color('warning'),
            Stat::make('Ditolak / Revisi', PsbOrder::where('status', PsbStatus::Rejected)->count())
                ->description('Menunggu revert')
                ->color('danger'),
        ];
    }
}
