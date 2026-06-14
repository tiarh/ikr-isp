<?php

namespace App\Providers;

use App\Services\CoverageService;
use App\Services\EbillingBridgeService;
use App\Services\PppoeGeneratorService;
use App\Services\PsbStateMachine;
use App\Services\SaleskitBridgeService;
use App\Services\TeknisiService;
use App\Services\WaNotificationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Vite;

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
        $this->registerBladeDirectives();
    }

    /**
     * Manually register Blade directives that would normally be
     * registered by package discovery (which is disabled in CI).
     *
     * Without these, the directives render as literal text in HTML.
     */
    private function registerBladeDirectives(): void
    {
        // Vite directives (@vite, @viteReactRefresh)
        Blade::directive('vite', function ($expression) {
            return "<?php echo app('Illuminate\\Foundation\\Vite')->__invoke({$expression}); ?>";
        });
        Blade::directive('viteReactRefresh', function () {
            return '<?php echo app("Illuminate\\Foundation\\Vite")->reactRefresh(); ?>';
        });
    }
}
