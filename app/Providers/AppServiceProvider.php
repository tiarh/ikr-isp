<?php

namespace App\Providers;

use App\Services\CoverageService;
use App\Services\EbillingBridgeService;
use App\Services\PppoeGeneratorService;
use App\Services\PsbStateMachine;
use App\Services\SaleskitBridgeService;
use App\Services\TeknisiService;
use App\Services\WaNotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CoverageService::class);
        $this->app->singleton(EbillingBridgeService::class);
        $this->app->singleton(PppoeGeneratorService::class);
        $this->app->singleton(WaNotificationService::class);
        $this->app->singleton(TeknisiService::class);
        $this->app->singleton(SaleskitBridgeService::class);
        $this->app->singleton(PsbStateMachine::class);
    }

    public function boot(): void
    {
        //
    }
}
