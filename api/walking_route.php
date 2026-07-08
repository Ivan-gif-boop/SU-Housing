<?php
// api/walking_route.php
// Server-side proxy for OpenRouteService foot-walking directions.
//
// The browser can't call api.openrouteservice.org directly — ORS
// doesn't send back an Access-Control-Allow-Origin header for
// localhost, so the request gets blocked by CORS policy. Proxying
// through our own backend avoids that entirely, and also keeps the
// ORS API key off the client (it would otherwise be visible in the
// page's JS source via DevTools).

require_once __DIR__ . '/../includes/headers.php';

$ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImFhYTMzMWIzMWE5OTQ4YzJhNjlhZWE0MDFmNGYxZmY4IiwiaCI6Im11cm11cjY0In0=';

$lat = (float) ($_GET['lat'] ?? 0);
$lng = (float) ($_GET['lng'] ?? 0);

if (!$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['error' => 'lat and lng are required.']);
    exit;
}

// Strathmore University main gate coordinates
$strathmoreLat = -1.3100;
$strathmoreLng = 36.8126;

$payload = json_encode([
    'coordinates' => [
        [$strathmoreLng, $strathmoreLat],
        [$lng, $lat],
    ],
]);

$ch = curl_init('https://api.openrouteservice.org/v2/directions/foot-walking/geojson');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: ' . $ORS_API_KEY,
        'Content-Type: application/json',
    ],
]);

$response   = curl_exec($ch);
$curlError  = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach OpenRouteService.', 'detail' => $curlError]);
    exit;
}

http_response_code($statusCode ?: 502);
echo $response;