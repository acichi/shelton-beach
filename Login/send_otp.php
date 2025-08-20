<?php
require_once __DIR__ . '/../config/env.php';

function sendSMS($recipients, $message) {
    // Prefer env-based configuration; fall back to previous defaults if not set
    $deviceId = (string)env('SMS_DEVICE_ID', '6887177d6eec0f67df7d3daa');
    $apiKey   = (string)env('SMS_API_KEY', '0d2de39f-8e1e-4996-a699-9d196a5e62d9');
    $baseUrl  = (string)env('SMS_API_URL', 'https://api.textbee.dev/api/v1/gateway');
    $url      = rtrim($baseUrl, '/') . "/devices/$deviceId/send-sms";

    $data = [
        'recipients' => $recipients,
        'message' => $message,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $apiKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)env('SMS_TIMEOUT', 15));

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    if ($response === false && $error === '' && $httpcode === 0) {
        $error = 'cURL execution failed without message';
    }

    curl_close($ch);

    $payload = [
        'status_code' => $httpcode,
        'response' => $response,
        'error' => $error,
    ];

    // Try to parse provider response for additional context (optional)
    if (is_string($response)) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded['message']) && !isset($payload['provider_message'])) {
                $payload['provider_message'] = $decoded['message'];
            }
            if (isset($decoded['error']) && !isset($payload['provider_error'])) {
                $payload['provider_error'] = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
            }
        }
    }

    return $payload;
}
 