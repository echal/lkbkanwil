<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Override Sanctum PersonalAccessToken to pin the database connection.
 *
 * Without this, Apache persistent connections shared between gaspul_api and
 * esaraku_helpdesk can cause Sanctum to query the wrong database (esaraku_helpdesk)
 * when validating Bearer tokens at /api/me.
 *
 * findToken() is called by Sanctum before any query — issuing USE here guarantees
 * the PDO connection context is correct even when Apache reuses a pooled connection
 * that was previously used by esaraku_helpdesk.
 */
class PersonalAccessToken extends SanctumToken
{
    protected $connection = 'mysql';

    public static function findToken($token): ?static
    {
        // Force correct DB context. Apache PDO pool may reuse a connection that
        // esaraku_helpdesk last set to USE esaraku_helpdesk — and config('database.
        // connections.mysql.database') also resolves to esaraku_helpdesk in that
        // process because the .env is loaded per-vhost. We always target the
        // canonical gaspul database by name, not by config.
        DB::connection('mysql')->statement('USE `gaspulco_lkbkanwil_db`');

        return parent::findToken($token);
    }

    /**
     * Override tokenable relationship to re-issue USE before the morphTo lazy-load.
     * Without this, the connection context set in findToken() may drift before
     * Sanctum Guard accesses $accessToken->tokenable.
     */
    public function tokenable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        DB::connection('mysql')->statement('USE `gaspulco_lkbkanwil_db`');

        return parent::tokenable();
    }
}
