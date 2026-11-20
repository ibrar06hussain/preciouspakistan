<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Cms\Models\CmsMenu;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Policies\CmsMenuPolicy;
use App\Modules\Cms\Policies\CmsPagePolicy;
use App\Modules\Cms\Services\MenuService;
use App\Modules\Cms\Services\SiteSettingsService;
use App\Modules\Cms\Support\ThemePresets;
use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Policies\DestinationModulePolicy;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Fleet\Policies\VehiclePolicy;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Hotels\Policies\HotelPolicy;
use App\Modules\Hotels\Policies\HotelVerificationPolicy;
use App\Modules\Identity\Contracts\MfaProvider;
use App\Modules\Identity\Models\AdminInvitation;
use App\Modules\Identity\Policies\AdminAccessPolicy;
use App\Modules\Identity\Policies\CustomerAccessPolicy;
use App\Modules\Identity\Services\NullMfaProvider;
use App\Modules\Rbac\Models\AuditLog;
use App\Modules\Rbac\Policies\AdminInvitationPolicy;
use App\Modules\Rbac\Policies\AuditLogPolicy;
use App\Modules\Rbac\Policies\RolePolicy;
use App\Modules\Rbac\Policies\UserPolicy;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Policies\TourPolicy;
use App\Shared\Support\DisplayTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AdminInvitation::class, AdminInvitationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(CmsMenu::class, CmsMenuPolicy::class);
        Gate::policy(Region::class, DestinationModulePolicy::class);
        Gate::policy(District::class, DestinationModulePolicy::class);
        Gate::policy(Destination::class, DestinationModulePolicy::class);
        Gate::policy(Attraction::class, DestinationModulePolicy::class);
        Gate::policy(Tour::class, TourPolicy::class);
        Gate::policy(Hotel::class, HotelPolicy::class);
        Gate::policy(HotelAvailabilityVerification::class, HotelVerificationPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(VehicleReservation::class, VehiclePolicy::class);

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

        RateLimiter::for('cms-contact', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('precious.rate_limits.api_per_minute', 60))
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    private function configureViewComposers(): void
    {
        View::composer(['layouts.*', 'public.*', 'cms.*', 'auth.*', 'customer.*', 'errors.*'], function (\Illuminate\View\View $view): void {
            if (! array_key_exists('siteSettings', $view->getData())) {
                $settings = app(SiteSettingsService::class)->allPublic();
                $view->with('siteSettings', $settings);
                $view->with('themeColorPreset', ThemePresets::resolveColorPreset($settings['theme_color_preset'] ?? null));
                $view->with('themeTypographyPreset', ThemePresets::resolveTypographyPreset($settings['theme_typography_preset'] ?? null));
            }

            if (! array_key_exists('cmsMenus', $view->getData())) {
                $menuService = app(MenuService::class);
                $view->with('cmsMenus', [
                    'header' => $menuService->getItemsForLocation(CmsMenu::LOCATION_HEADER),
                    'mobile' => $menuService->getItemsForLocation(CmsMenu::LOCATION_MOBILE),
                    'footer_primary' => $menuService->getItemsForLocation(CmsMenu::LOCATION_FOOTER_PRIMARY),
                    'footer_legal' => $menuService->getItemsForLocation(CmsMenu::LOCATION_FOOTER_LEGAL),
                ]);
            }
        });
    }

    private function configureBladeDirectives(): void
    {
        Blade::directive('pktDate', function (string $expression): string {
            return "<?php echo e(\\App\\Shared\\Support\\DisplayTimezone::format({$expression})); ?>";
        });

        DisplayTimezone::timezone();
    }
}
