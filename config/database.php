<?php
// config/database.php - Universal Database Configuration (Local XAMPP, php -S, & Hostinger)

// 1. Load config/db_config.php if created by installer or user
$dbConfigFile = __DIR__ . '/db_config.php';
$customConfig = file_exists($dbConfigFile) ? require $dbConfigFile : [];

// 2. Load .env file if present
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!getenv($key)) {
                putenv("{$key}={$val}");
            }
        }
    }
}

// 3. Define database constants with smart fallback hierarchy:
// Priority: customConfig -> getenv -> default local XAMPP / MySQL 8.0
if (!defined('DB_HOST')) define('DB_HOST', $customConfig['host'] ?? getenv('DB_HOST') ?: '194.59.164.63');
if (!defined('DB_PORT')) define('DB_PORT', $customConfig['port'] ?? getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', $customConfig['database'] ?? getenv('DB_NAME') ?: 'u534933225_bfms');
if (!defined('DB_USER')) define('DB_USER', $customConfig['username'] ?? getenv('DB_USER') ?: 'u534933225_bfms');
if (!defined('DB_PASS')) define('DB_PASS', $customConfig['password'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Ia3J$~;~3e!'));

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Try primary configured password, followed by empty and 'root' fallbacks for local dev
    $passwordsToTry = [DB_PASS];
    if (DB_PASS !== '') {
        $passwordsToTry[] = '';
    }
    if (DB_PASS !== 'root') {
        $passwordsToTry[] = 'root';
    }

    $lastException = null;

    foreach ($passwordsToTry as $pwd) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, $pwd, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            
            // If database doesn't exist (Error 1049) and running with root locally, try creating it
            if ($e->getCode() == 1049 && DB_USER === 'root') {
                try {
                    $serverPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, $pwd);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Reconnect to newly created database
                    $pdo = new PDO($dsn, DB_USER, $pwd, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                    return $pdo;
                } catch (Exception $ex) {
                    $lastException = $ex;
                }
            }
        }
    }

    // Friendly error page with setup wizard link if connection fails
    $installUrl = function_exists('url') ? url('install.php') : 'install.php';
    die("<div style='font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;padding:30px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;border-radius:10px;max-width:680px;margin:60px auto;box-shadow:0 10px 25px rgba(0,0,0,0.08);'>
        <h2 style='margin-top:0;color:#b91c1c;'>Database Connection Required</h2>
        <p>Could not connect to MySQL database <strong>`" . htmlspecialchars(DB_NAME) . "`</strong> on host <strong>`" . htmlspecialchars(DB_HOST) . "`</strong>.</p>
        <div style='background:#fee2e2;padding:12px;border-radius:6px;font-family:monospace;font-size:13px;word-break:break-all;margin-bottom:18px;'>
            " . htmlspecialchars($lastException ? $lastException->getMessage() : 'Access denied or server unreachable') . "
        </div>
        <p>If you are deploying to <strong>Hostinger</strong> or local server, you can configure your credentials using the Web Installer:</p>
        <p><a href='{$installUrl}' style='display:inline-block;padding:10px 20px;background:#059669;color:#fff;text-decoration:none;font-weight:bold;border-radius:6px;'>Launch Database Setup Wizard &rarr;</a></p>
    </div>");
}
