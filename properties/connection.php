<?php
require_once __DIR__ . '/../config/env.php';

$server = (string)env('DB_HOST', 'localhost');
$username = (string)env('DB_USER', 'root');
$password = (string)env('DB_PASS', '');
$database = (string)env('DB_NAME', 'shelton_db');
$port = (int)env('DB_PORT', 3306);

$conn = mysqli_connect($server, $username, $password, $database, $port);
if(!$conn){
    // Avoid leaking detailed connection errors to users
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $isFetch = isset($_SERVER['HTTP_SEC_FETCH_MODE']);
    if ($isAjax || $isFetch || stripos($accept, 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'error'=>'Database connection error.']);
        exit;
    }
    exit('Database connection error.');
}

// Ensure proper charset/colllation
$charset = (string)env('DB_CHARSET', 'utf8mb4');
@mysqli_set_charset($conn, $charset);
?>