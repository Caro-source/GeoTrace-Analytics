<?php
/**
 * GeoTrace Analytics - lookup endpoint
 *
 * Looks up an IP address or domain name, saves the result to the
 * "lookups" table, and returns it as JSON.
 *
 * Usage:
 *   GET  api/lookup.php?target=8.8.8.8       -> look up an IP
 *   GET  api/lookup.php?target=example.com   -> resolve a domain, then look up its IP
 *   GET  api/lookup.php                      -> look up YOUR public IP
 *   POST api/lookup.php  with JSON {"target": "..."} or form field target=...
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require_method('GET', 'POST');

// ---------------------------------------------------------------
// 1. Read and clean the input
// ---------------------------------------------------------------

$target = $_GET['target'] ?? $_POST['target'] ?? null;

if ($target === null && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($body) && isset($body['target'])) {
        $target = $body['target'];
    }
}

if ($target !== null && !is_string($target)) {
    send_error('"target" must be text.', 400);
}

$target = normalize_target((string) $target);

if (strlen($target) > 253) {
    send_error('That input is too long to be an IP address or domain.', 400);
}

// ---------------------------------------------------------------
// 2. Work out what kind of target it is and which IP to look up
// ---------------------------------------------------------------

$clientIp   = $_SERVER['REMOTE_ADDR'] ?? null;
$resolvedIp = null;
$apiQuery   = null;   // what we send to the geolocation API ('' = "whoever is calling")

if ($target === '') {
    // "Detect my IP". On a live server REMOTE_ADDR is the visitor's IP.
    // On XAMPP it is 127.0.0.1, so we let the API report the public IP
    // of this machine instead, which is the same thing for you at home.
    $type = 'self';
    $queryInput = 'my-ip';
    if ($clientIp !== null && is_public_ip($clientIp)) {
        $apiQuery = $clientIp;
        $resolvedIp = $clientIp;
    } else {
        $apiQuery = '';
    }
} elseif (filter_var($target, FILTER_VALIDATE_IP) !== false) {
    $type = 'ip';
    $queryInput = $target;
    $resolvedIp = $target;
    $apiQuery = $target;
} elseif (is_valid_domain($target)) {
    $type = 'domain';
    $queryInput = strtolower($target);
    $resolvedIp = resolve_domain($queryInput);
    $apiQuery = $resolvedIp;
} else {
    send_error('Please enter a valid IP address (like 8.8.8.8) or domain (like example.com).', 400);
}

// ---------------------------------------------------------------
// 3. Look it up
// ---------------------------------------------------------------

$row = [
    'query_input' => $queryInput,
    'query_type'  => $type,
    'resolved_ip' => $resolvedIp,
    'client_ip'   => $clientIp,
];

if ($type === 'domain' && $resolvedIp === null) {
    $row += fail_fields('Domain could not be resolved to an IP address.');
} elseif ($apiQuery !== '' && !is_public_ip($apiQuery)) {
    $row += fail_fields('Private or reserved IP address (no public location data).');
} else {
    try {
        $row += geolocate($apiQuery, $CONFIG);
        if ($type === 'self' && $row['resolved_ip'] === null) {
            $row['resolved_ip'] = $row['_api_ip'] ?? null;
        }
    } catch (RuntimeException $e) {
        send_error('The geolocation service could not be reached. Try again in a minute.', 502, $e);
    }
}
unset($row['_api_ip']);

// ---------------------------------------------------------------
// 4. Save it to the history table
// ---------------------------------------------------------------

$saved = false;
$warning = null;
try {
    $columns = array_keys($row);
    $sql = sprintf(
        'INSERT INTO lookups (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', array_map(fn ($c) => ':' . $c, $columns))
    );
    $stmt = db()->prepare($sql);
    $stmt->execute($row);
    $row['id'] = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT created_at FROM lookups WHERE id = ?');
    $stmt->execute([$row['id']]);
    $row['created_at'] = $stmt->fetchColumn() ?: null;
    $saved = true;
} catch (PDOException $e) {
    $warning = 'Lookup worked but could not be saved to the database. Is MySQL running in XAMPP?';
    if (!empty($CONFIG['debug'])) {
        $warning .= ' (' . $e->getMessage() . ')';
    }
}

// ---------------------------------------------------------------
// 5. Reply
// ---------------------------------------------------------------

$response = [
    'ok'    => $row['status'] === 'success',
    'saved' => $saved,
    'data'  => format_row($row),
];
if ($row['status'] !== 'success') {
    $response['error'] = $row['error_message'];
}
if ($warning !== null) {
    $response['warning'] = $warning;
}

send_json($response, $row['status'] === 'success' ? 200 : 422);


// ===============================================================
// Helper functions
// ===============================================================

/** Trim input and strip things people often paste, like "https://" or a path. */
function normalize_target(string $input): string
{
    $t = trim($input);
    $t = preg_replace('/[\x00-\x1F\x7F]/', '', $t) ?? '';   // drop control characters

    // "https://example.com/page" -> "example.com"
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $t)) {
        $host = parse_url($t, PHP_URL_HOST);
        $t = is_string($host) ? $host : '';
    }

    // "[2001:db8::1]" -> "2001:db8::1"
    $t = trim($t, '[]');

    // "example.com." (trailing dot) -> "example.com"
    return rtrim($t, '.');
}

function is_valid_domain(string $s): bool
{
    return str_contains($s, '.')
        && filter_var($s, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
        && preg_match('/[a-z]/i', substr($s, strrpos($s, '.') + 1)) === 1; // TLD has a letter
}

function is_public_ip(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

/** Return the first IPv4 (or IPv6) address for a domain, or null. */
function resolve_domain(string $domain): ?string
{
    $ipv4 = @gethostbynamel($domain);
    if (is_array($ipv4) && $ipv4 !== []) {
        return $ipv4[0];
    }
    $records = @dns_get_record($domain, DNS_AAAA);
    if (is_array($records) && isset($records[0]['ipv6'])) {
        return $records[0]['ipv6'];
    }
    return null;
}

/** Fields for a failed lookup. */
function fail_fields(string $message): array
{
    return ['status' => 'fail', 'error_message' => $message];
}

/**
 * Ask ip-api.com where an IP is. Pass '' to look up the caller's own IP.
 * Throws RuntimeException if the service cannot be reached.
 */
function geolocate(string $ip, array $config): array
{
    $fields = 'status,message,query,country,countryCode,regionName,city,zip,lat,lon,timezone,isp,org,as';
    $url = rtrim($config['geo_api_url'], '/') . '/' . rawurlencode($ip) . '?fields=' . $fields;

    $raw = http_get($url, (int) ($config['geo_api_timeout'] ?? 5));
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['status'])) {
        throw new RuntimeException('Unexpected response from geolocation service.');
    }

    if ($data['status'] !== 'success') {
        return fail_fields('Lookup failed: ' . ($data['message'] ?? 'unknown reason'));
    }

    return [
        'status'        => 'success',
        'error_message' => null,
        'country'       => $data['country'] ?? null,
        'country_code'  => $data['countryCode'] ?? null,
        'region'        => $data['regionName'] ?? null,
        'city'          => $data['city'] ?? null,
        'zip'           => ($data['zip'] ?? '') !== '' ? $data['zip'] : null,
        'latitude'      => $data['lat'] ?? null,
        'longitude'     => $data['lon'] ?? null,
        'timezone'      => $data['timezone'] ?? null,
        'isp'           => $data['isp'] ?? null,
        'org'           => ($data['org'] ?? '') !== '' ? $data['org'] : null,
        'as_name'       => ($data['as'] ?? '') !== '' ? $data['as'] : null,
        '_api_ip'       => $data['query'] ?? null,
    ];
}

/** Download a URL with cURL (falls back to file_get_contents). */
function http_get(string $url, int $timeout): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_USERAGENT      => 'GeoTrace-Analytics/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            throw new RuntimeException("HTTP request failed (code $code) $err");
        }
        return (string) $body;
    }

    $ctx = stream_context_create(['http' => ['timeout' => $timeout]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException('HTTP request failed.');
    }
    return $body;
}

/** Shape a row for the JSON response (numbers as numbers, nice key names). */
function format_row(array $row): array
{
    return [
        'id'          => $row['id'] ?? null,
        'query'       => $row['query_input'],
        'type'        => $row['query_type'],
        'ip'          => $row['resolved_ip'],
        'status'      => $row['status'],
        'country'     => $row['country'] ?? null,
        'countryCode' => $row['country_code'] ?? null,
        'region'      => $row['region'] ?? null,
        'city'        => $row['city'] ?? null,
        'zip'         => $row['zip'] ?? null,
        'lat'         => isset($row['latitude']) ? (float) $row['latitude'] : null,
        'lon'         => isset($row['longitude']) ? (float) $row['longitude'] : null,
        'timezone'    => $row['timezone'] ?? null,
        'isp'         => $row['isp'] ?? null,
        'org'         => $row['org'] ?? null,
        'as'          => $row['as_name'] ?? null,
        'createdAt'   => $row['created_at'] ?? null,
    ];
}
