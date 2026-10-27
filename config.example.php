<?php
/**
 * GeoTrace Analytics - configuration TEMPLATE.
 *
 * 1. Copy this file and name the copy "config.php" (same folder).
 * 2. Put your real database settings in config.php.
 *
 * config.php is listed in .gitignore, so your password never reaches GitHub.
 * The values below are the XAMPP defaults on a fresh install.
 */

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'geotrace',
        'user'     => 'root',
        'password' => '',          // XAMPP's root user has no password by default
        'charset'  => 'utf8mb4',
    ],

    // Free geolocation service (no API key needed, 45 requests/minute).
    'geo_api_url' => 'http://ip-api.com/json/',

    // Seconds to wait for the geolocation service before giving up.
    'geo_api_timeout' => 5,

    // Show detailed error messages in API responses. Turn OFF on the live site.
    'debug' => true,
];
