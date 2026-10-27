<?php
/**
 * Shared setup for every API endpoint: loads config, connects to MySQL,
 * and provides small helpers for sending JSON responses.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'config.php is missing. Copy config.example.php to config.php and fill in your database settings.',
    ]);
    exit;
}

$CONFIG = require $configFile;

/** Send a JSON response and stop. */
function send_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Send an error response. Extra detail is only shown when debug is on. */
function send_error(string $message, int $status, ?Throwable $e = null): never
{
    global $CONFIG;
    $payload = ['ok' => false, 'error' => $message];
    if ($e !== null && !empty($CONFIG['debug'])) {
        $payload['detail'] = $e->getMessage();
    }
    send_json($payload, $status);
}

/** Return a shared PDO connection to MySQL (created on first use). */
function db(): PDO
{
    global $CONFIG;
    static $pdo = null;

    if ($pdo === null) {
        $c = $CONFIG['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['name'],
            $c['charset']
        );
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

/** Only allow the listed HTTP methods for this endpoint. */
function require_method(string ...$allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        send_error('Method not allowed. Use ' . implode(' or ', $allowed) . '.', 405);
    }
}
