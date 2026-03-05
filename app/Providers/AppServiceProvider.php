<?php

namespace App\Providers;

use App\Models\PropertyType;
use App\Models\PropertyTypeAttribute;
use App\Models\Region;
use App\Observers\PropertyTypeAttributeObserver;
use App\Observers\PropertyTypeObserver;
use App\Observers\RegionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        PropertyType::observe(PropertyTypeObserver::class);
        PropertyTypeAttribute::observe(PropertyTypeAttributeObserver::class);
        Region::observe(RegionObserver::class);

        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super-Admin')) {
                return true;
            }
        });
    }
}
