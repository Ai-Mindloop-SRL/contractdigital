<?php
/**
 * CUI Lookup via OpenAPI.ro - MINDLOOP ONLY
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
$cui = $input['cui'] ?? '';

// Clean CUI (remove RO prefix, spaces, etc.)
$cui = preg_replace('/[^0-9]/', '', $cui);

if (empty($cui)) {
    http_response_code(400);
    die(json_encode(['error' => 'CUI lipsă']));
}

// OpenAPI.ro credentials
$api_key = 'cD6JjaZE5tz_4ycW3QD-8ossPrg7cPSTFewsusmqDyKSmoawTw';
$api_url = 'https://api.openapi.ro/api/companies/' . $cui;

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code($http_code);
    die(json_encode(['error' => 'CUI invalid sau firmă negăsită']));
}

$data = json_decode($response, true);

// Return relevant fields
echo json_encode([
    'success' => true,
    'denumire' => $data['denumire'] ?? '',
    'numar_reg_com' => $data['numar_reg_com'] ?? '',
    'adresa' => $data['adresa'] ?? '',
    'telefon' => $data['telefon'] ?? '',
    'cui' => $data['cif'] ?? $cui
]);
