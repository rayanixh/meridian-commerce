<?php
/**
 * Database connection (singleton PDO).
 * Reads config from db_config.php which is written by the installer.
 */

function db(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $file = __DIR__ . '/db_config.php';
    if (!file_exists($file)) return null;

    $cfg = require $file;
    if (!is_array($cfg) || empty($cfg['host']) || empty($cfg['name'])) return null;

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $cfg['host'],
            $cfg['port'] ?? '3306',
            $cfg['name']
        );
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
        return $pdo;
    } catch (PDOException $e) {
        log_error('db', $e->getMessage());
        return null;
    }
}

function db_config_exists(): bool {
    return file_exists(__DIR__ . '/db_config.php');
}

function write_db_config(array $c): bool {
    $payload = "<?php\nreturn " . var_export([
        'host' => $c['host'] ?? 'localhost',
        'port' => $c['port'] ?? '3306',
        'name' => $c['name'] ?? '',
        'user' => $c['user'] ?? '',
        'pass' => $c['pass'] ?? '',
    ], true) . ";\n";
    return file_put_contents(__DIR__ . '/db_config.php', $payload) !== false;
}
