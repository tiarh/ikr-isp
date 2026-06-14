<?php

namespace App\Providers;

use App\Models\PsbOrder;
use App\Observers\PsbOrderObserver;
use Illuminate\Support\ServiceProvider;

class PsbServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PsbOrder::observe(PsbOrderObserver::class);
    }
}
