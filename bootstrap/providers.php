<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\PsbServiceProvider::class,
    Livewire\LivewireServiceProvider::class,
    Filament\FilamentServiceProvider::class,
    Filament\Forms\FormsServiceProvider::class,
    Filament\Tables\TablesServiceProvider::class,
    Filament\Actions\ActionsServiceProvider::class,
    Filament\Notifications\NotificationsServiceProvider::class,
    Filament\Infolists\InfolistsServiceProvider::class,
    Filament\Widgets\WidgetsServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
