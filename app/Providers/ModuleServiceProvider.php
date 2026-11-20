<?php

namespace App\Providers;

use App\Modules\Cms\Services\SiteSettingsService;
use App\Modules\Identity\Contracts\MfaProvider;
use App\Modules\Identity\Policies\AdminAccessPolicy;
use App\Modules\Identity\Policies\CustomerAccessPolicy;
use App\Modules\Identity\Services\NullMfaProvider;
use App\Shared\Support\DisplayTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettingsService::class);
        $this->app->bind(MfaProvider::class, NullMfaProvider::class);
    }

    public function boot(): void
    {
        $this->configureGates();
        $this->configureRateLimiters();
        $this->configureViewComposers();
        $this->configureBladeDirectives();
    }

    private function configureGates(): void
    {
        Gate::define('accessAdminPanel', [AdminAccessPolicy::class, 'accessPanel']);
        Gate::define('inviteStaff', [AdminAccessPolicy::class, 'inviteStaff']);
        Gate::define('accessCustomerDashboard', [CustomerAccessPolicy::class, 'accessDashboard']);
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute((int) config('precious.rate_limits.login_per_minute', 5))->by($key);
        });

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute((int) config('precious.rate_limits.register_per_minute', 5))->by((string) $request->ip());
        });

        RateLimiter::for('auth-password-reset', function (Request $request): Limit {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute((int) config('precious.rate_limits.password_reset_per_minute', 5))->by($key);
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('precious.rate_limits.api_per_minute', 60))
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    private function configureViewComposers(): void
    {
        View::composer(['layouts.*', 'public.*', 'auth.*', 'customer.*', 'errors.*'], function (\Illuminate\View\View $view): void {
            if (! array_key_exists('siteSettings', $view->getData())) {
                $view->with('siteSettings', app(SiteSettingsService::class)->allPublic());
            }
        });
    }

    private function configureBladeDirectives(): void
    {
        Blade::directive('pktDate', function (string $expression): string {
            return "<?php echo e(\\App\\Shared\\Support\\DisplayTimezone::format({$expression})); ?>";
        });

        // Verify DisplayTimezone class is autoloadable early.
        DisplayTimezone::timezone();
    }
}
