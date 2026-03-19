<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$symbols = $_GET['symbols'] ?? '';

if ($symbols === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing symbols parameter.'
    ]);
    exit;
}

$apiUrl = 'https://api.paper-trade.org/landing?symbols=' . urlencode($symbols);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false || $curlError) {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reach market API.',
        'details' => $curlError
    ]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'Market API returned an invalid response.',
        'http_code' => $httpCode
    ]);
    exit;
}

echo $response;