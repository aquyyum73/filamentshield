<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //Policy::register(Activity::class, ActivityPolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);

        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
