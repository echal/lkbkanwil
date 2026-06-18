<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Policies\SkpTahunanPolicy;
use App\Policies\SkpTahunanDetailPolicy;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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

        // Pin Sanctum to explicit 'mysql' connection — prevents Apache shared PDO
        // from bleeding esaraku_helpdesk's DB context into gaspul_api token lookups.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Force correct DB context at boot time — Apache PDO persistent connections
        // may be shared between gaspul_api and esaraku_helpdesk, causing the active
        // database to bleed across requests. We issue USE once at bootstrap so every
        // query in this request hits the correct database.
        try {
            \Illuminate\Support\Facades\DB::connection('mysql')->statement('USE `gaspulco_lkbkanwil_db`');
        } catch (\Throwable $e) {
            // Non-fatal — DB may not be needed on this request
        }

        // Phase J — Production Hardening (J-01): catch the most dangerous
        // misconfiguration — debug mode left on in a production environment,
        // which leaks stack traces and .env values to anyone who triggers an
        // error. This only logs (does not abort the request) to avoid causing
        // unplanned downtime; the deployment checklist in
        // docs/PHASE_J_PRODUCTION_HARDENING_REPORT.md is the primary control.
        if ($this->app->environment('production') && config('app.debug')) {
            \Illuminate\Support\Facades\Log::critical(
                'SECURITY: APP_DEBUG=true while APP_ENV=production. Stack traces and .env values are exposed to end users. Set APP_DEBUG=false immediately.'
            );
        }
    }
}
