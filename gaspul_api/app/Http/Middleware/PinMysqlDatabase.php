<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every request to ensure the MySQL PDO connection is pointing to the
 * correct database. Apache's persistent PDO pool may reuse a connection that
 * was previously used by esaraku_helpdesk, leaving the active database set to
 * esaraku_helpdesk instead of gaspulco_lkbkanwil_db.
 */
class PinMysqlDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            DB::connection('mysql')->statement('USE `gaspulco_lkbkanwil_db`');
        } catch (\Throwable) {
            // Non-fatal: DB may not be needed on this request path
        }

        return $next($request);
    }
}
