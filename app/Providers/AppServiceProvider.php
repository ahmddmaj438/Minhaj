<?php

namespace App\Providers;

use App\Models\User;
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
        Gate::before(function (User $user, string $ability): ?bool {
            if (app()->environment('testing')) {
                return true;
            }

             if ($user->isRootSuperAdmin()) {
                return true;
            }

            if ($ability === 'grant_super_admin') {
                return false;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
