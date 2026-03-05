<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PropertyTypeAttribute;
use App\Models\Region;
use App\Models\User;
use App\Observers\PropertyObserver;
use App\Observers\PropertyTypeAttributeObserver;
use App\Observers\PropertyTypeObserver;
use App\Observers\RegionObserver;
use App\Policies\AttributePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\PropertyTypeAttributePolicy;
use App\Policies\PropertyTypePolicy;
use App\Policies\RegionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        // Observers
        Property::observe(PropertyObserver::class);
        PropertyType::observe(PropertyTypeObserver::class);
        PropertyTypeAttribute::observe(PropertyTypeAttributeObserver::class);
        Region::observe(RegionObserver::class);

        // Policies
        // Explicitly registered to avoid relying on auto-discovery conventions,
        // especially for non-standard model/policy name mappings.
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(PropertyType::class, PropertyTypePolicy::class);
        Gate::policy(PropertyTypeAttribute::class, PropertyTypeAttributePolicy::class);
        Gate::policy(Attribute::class, AttributePolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);

        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super-Admin')) {
                return true;
            }
        });
    }
}
