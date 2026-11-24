<?php
header('Content-Type: application/geo+json; charset=utf-8');

if (!function_exists('curl_init')) {
    error_log('Unable to load energy structure data: PHP cURL extension is not enabled.');
    http_response_code(500);
    echo json_encode(['error' => 'The PHP cURL extension is required.']);
    exit;
}

$parameters = [
    'service' => 'WFS',
    'version' => '1.0.0',
    'request' => 'GetFeature',
    'typeName' => 'pg_web:struktur_aceh',
    'outputFormat' => 'application/json',
    'srsName' => 'EPSG:4326',
];
$url = 'http://localhost:8080/geoserver/pg_web/ows?' .
    http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

$curl = curl_init($url);
if ($curl === false) {
    error_log('Unable to initialize the GeoServer WFS request.');
    http_response_code(500);
    echo json_encode(['error' => 'Unable to initialize the GeoServer request.']);
    exit;
}

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if (!is_string($response) || $status < 200 || $status >= 300) {
    error_log('GeoServer WFS request failed (HTTP ' . $status . '): ' . $curlError);
    http_response_code(502);
    echo json_encode(['error' => 'GeoServer did not return the energy structure data.']);
    exit;
}

$geojson = json_decode($response, true);
if (!is_array($geojson) || ($geojson['type'] ?? null) !== 'FeatureCollection' ||
    !isset($geojson['features']) || !is_array($geojson['features'])) {
    error_log('GeoServer WFS returned an invalid GeoJSON FeatureCollection.');
    http_response_code(502);
    echo json_encode(['error' => 'GeoServer returned invalid GeoJSON.']);
    exit;
}

echo $response;
