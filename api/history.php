<?php
/**
 * GeoTrace Analytics - lookup history endpoint
 *
 * Returns the most recent lookups saved in the database, newest first.
 *
 * Usage:
 *   GET api/history.php              -> last 50 lookups
 *   GET api/history.php?limit=10     -> last 10 (max 500)
 *   GET api/history.php?status=success
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require_method('GET');

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, [
    'options' => ['default' => 50, 'min_range' => 1, 'max_range' => 500],
]);
if ($limit === false) {
    $limit = 50;
}

$status = $_GET['status'] ?? null;
if ($status !== null && !in_array($status, ['success', 'fail'], true)) {
    send_error('"status" must be "success" or "fail".', 400);
}

try {
    $sql = 'SELECT id, query_input, query_type, resolved_ip, status, error_message,
                   country, country_code, region, city, zip, latitude, longitude,
                   timezone, isp, org, as_name, created_at
            FROM lookups'
        . ($status !== null ? ' WHERE status = :status' : '')
        . ' ORDER BY id DESC LIMIT :limit';

    $stmt = db()->prepare($sql);
    if ($status !== null) {
        $stmt->bindValue(':status', $status);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    send_error('Could not read history from the database. Is MySQL running in XAMPP?', 500, $e);
}

$items = array_map(fn (array $r) => [
    'id'          => (int) $r['id'],
    'query'       => $r['query_input'],
    'type'        => $r['query_type'],
    'ip'          => $r['resolved_ip'],
    'status'      => $r['status'],
    'error'       => $r['error_message'],
    'country'     => $r['country'],
    'countryCode' => $r['country_code'],
    'region'      => $r['region'],
    'city'        => $r['city'],
    'zip'         => $r['zip'],
    'lat'         => $r['latitude'] !== null ? (float) $r['latitude'] : null,
    'lon'         => $r['longitude'] !== null ? (float) $r['longitude'] : null,
    'timezone'    => $r['timezone'],
    'isp'         => $r['isp'],
    'org'         => $r['org'],
    'as'          => $r['as_name'],
    'createdAt'   => $r['created_at'],
], $rows);

send_json(['ok' => true, 'count' => count($items), 'data' => $items]);
