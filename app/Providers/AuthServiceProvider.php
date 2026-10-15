<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Pimpinan / Admin Gudang memiliki otorisasi penuh di seluruh modul sistem
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin_gudang') || $user->hasRole('supervisor') || !empty($user->can_access_all_departments)) {
                return true;
            }
            return null;
        });
    }
}
