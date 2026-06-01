<?php

// Fast health check - responds before Laravel loads
if ($_SERVER['REQUEST_URI'] === '/health' || $_SERVER['REQUEST_URI'] === '/health/') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    http_response_code(200);
    echo 'OK';
    exit;
}

// Fast root response for health checks
if ($_SERVER['REQUEST_URI'] === '/' && 
    (empty($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false)) {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo 'OK';
    exit;
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
