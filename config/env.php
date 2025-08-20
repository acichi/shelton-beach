<?php
// Lightweight .env loader and env() helper

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') return $default;
        // Normalize booleans and numbers
        $lower = strtolower((string)$val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if (is_numeric($val)) return $val + 0;
        return $val;
    }
}

if (!function_exists('loadEnvFromFile')) {
    function loadEnvFromFile(string $filePath): void {
        if (!is_readable($filePath)) return;
        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || str_starts_with($line, '//')) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) { $v = trim($v, "\"'"); }
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
            @putenv($k . '=' . $v);
        }
    }
}

if (!function_exists('loadEnv')) {
    function loadEnv(string $dirPath): void {
        $base = rtrim($dirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // Try multiple filenames to accommodate environments where dotfiles are blocked
        $candidates = [
            $base . '.env',
            $base . 'env',
        ];
        foreach ($candidates as $file) {
            loadEnvFromFile($file);
        }
    }
}

// Auto-load from project root one level up from config/
loadEnv(dirname(__DIR__));


