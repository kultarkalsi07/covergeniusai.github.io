<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$apiKey = 'AIzaSyDhqMqdvA6HUy3AQ-1_9Rtc20jFIKSy2qw'; // Replace with your actual API key
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl . '?key=' . $apiKey,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => true, // Enable SSL verification
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'contents' => [[
            'parts' => [[
                'text' => $data['prompt']
            ]]
        ]]
    ])
]);

// Execute cURL request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check HTTP response code
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'API request failed with status code: ' . $httpCode]);
    exit;
}

// Decode and validate response
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON response from API']);
    exit;
}

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['coverLetter' => $result['candidates'][0]['content']['parts'][0]['text']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid response structure from AI service']);
}