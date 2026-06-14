<?php

namespace App\Filament\Resources\PsbOrderResource\Pages;

use App\Filament\Resources\PsbOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPsbOrder extends ViewRecord
{
    protected static string $resource = PsbOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
