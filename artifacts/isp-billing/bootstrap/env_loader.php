<?php
/**
 * Environment variable loader for Replit deployment
 * This script ensures environment variables are available to PHP
 * regardless of how they are passed by the host environment.
 */

// Only run once
if (defined('ENV_LOADER_RAN')) {
    return;
}
define('ENV_LOADER_RAN', true);

// Keys we need from environment
$envKeys = [
    'DATABASE_URL',
    'PGHOST',
    'PGPORT',
    'PGDATABASE',
    'PGUSER',
    'PGPASSWORD',
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'LOG_LEVEL',
    'SESSION_ENCRYPT',
    'SESSION_LIFETIME',
    'SESSION_SECURE_COOKIE',
];

// Try to get values from various sources and ensure they're in getenv()
foreach ($envKeys as $key) {
    $value = null;
    
    // Check getenv
    if (getenv($key) !== false) {
        continue; // Already set
    }
    
    // Check $_ENV
    if (isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    // Check $_SERVER
    elseif (isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }
    
    // If we found a value, set it in the environment
    if ($value !== null) {
        putenv("$key=$value");
    }
}
