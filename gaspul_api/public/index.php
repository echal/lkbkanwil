<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Phase I.2A-B — Runtime Isolation Fix
// Disables the putenv()/getenv() adapter Dotenv uses to load .env values.
// Apache's mod_php runs gaspul_api and esaraku_helpdesk in the same OS process;
// putenv() writes are process-global and survive across requests on the same
// worker thread, so one app's .env can "bleed" into the other's. Without this,
// Laravel's immutable env repository refuses to overwrite the bled-in value,
// causing wrong session cookie/config resolution and unexpected logouts.
\Illuminate\Support\Env::disablePutenv();

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
