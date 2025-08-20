<?php
// PayMongo configuration via .env (supports TEST and LIVE)
require_once __DIR__ . '/env.php';

// Mode: TEST or LIVE
$mode = strtoupper((string)env('PAYMONGO_MODE', 'TEST'));

$secretKey = $mode === 'LIVE' ? env('PAYMONGO_LIVE_SECRET') : env('PAYMONGO_TEST_SECRET', 'sk_test_Zr135G7DMf2FD5XqVkG7dHTn');
$publicKey = $mode === 'LIVE' ? env('PAYMONGO_LIVE_PUBLIC') : env('PAYMONGO_TEST_PUBLIC', 'pk_test_Gp3xZQQfjAt2uUqxmeeMWSK9');

// Absolute URLs for redirects
$baseUrl = rtrim((string)env('APP_BASE_URL', ''), '/');
if ($baseUrl === '') {
    // Fallback to building from current request (best-effort)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host . '/sbh';
}

define('PAYMONGO_SECRET_KEY', $secretKey);
define('PAYMONGO_PUBLIC_KEY', $publicKey);
define('PAYMONGO_CHECKOUT_SUCCESS_URL', $baseUrl . '/customerdash/receipt.php');
define('PAYMONGO_CHECKOUT_FAILED_URL',  $baseUrl . '/customerdash/cusdash.php');

// Webhook signature secret (optional but recommended). Set PAYMONGO_WEBHOOK_SECRET in .env
define('PAYMONGO_WEBHOOK_SECRET', (string)env('PAYMONGO_WEBHOOK_SECRET', ''));


