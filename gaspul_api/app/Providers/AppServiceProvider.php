<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\SkpTahunanDetail;
use App\Policies\SkpTahunanDetailPolicy;

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
        // Register Policy
        Gate::policy(SkpTahunanDetail::class, SkpTahunanDetailPolicy::class);
    }
}
