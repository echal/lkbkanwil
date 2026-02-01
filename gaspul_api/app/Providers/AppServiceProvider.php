<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Policies\SkpTahunanPolicy;
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
        // Register Policies
        Gate::policy(SkpTahunan::class, SkpTahunanPolicy::class);
        Gate::policy(SkpTahunanDetail::class, SkpTahunanDetailPolicy::class);
    }
}
