<?php
// =============================================================================
// includes/db.php — Lidhja me databazën (PDO Singleton)
// =============================================================================

require_once dirname(__DIR__) . '/config/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME
                 . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    $setup_url = ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http')
                    . '://' . ($_SERVER['HTTP_HOST']??'localhost')
                    . str_replace('/config/config.php','',str_replace($_SERVER['DOCUMENT_ROOT']??'','',__FILE__))
                    . '/../setup.php';
                die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Gabim DB — ProEstate</title>'
                    . '<style>body{font-family:system-ui;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
                    . '.box{background:#fff;border-radius:12px;padding:40px;max-width:500px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.1)}'
                    . 'h2{color:#dc2626;margin-bottom:12px}p{color:#6b7280;font-size:.9rem;margin-bottom:20px}'
                    . 'code{background:#f0f2f5;padding:4px 8px;border-radius:4px;font-size:.85rem}'
                    . 'a{display:inline-block;padding:10px 24px;background:#0a1628;color:#fff;border-radius:8px;text-decoration:none;font-weight:700}'
                    . '</style></head><body><div class="box">'
                    . '<h2>⚠️ Gabim Databaze</h2>'
                    . '<p>ProEstate nuk mund të lidhet me MySQL.<br><strong>Mesazhi:</strong> <code>' . htmlspecialchars($e->getMessage()) . '</code></p>'
                    . '<p>Kontrollo kredencialet në <code>config/config.php</code></p>'
                    . '<a href="setup.php">🔧 Hap Setup</a>'
                    . '</div></body></html>');
                } else {
                    die('Gabim i brendshëm. Ju lutemi provoni më vonë.');
                }
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}

// Shorthand helper
function db(): PDO {
    return Database::getInstance();
}

/**
 * Execute a prepared statement and return the statement object
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row
 */
function db_row(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}

/**
 * Fetch all rows
 */
function db_rows(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Get the last insert ID
 */
function db_last_id(): string {
    return db()->lastInsertId();
}

/**
 * Count rows
 */
function db_count(string $sql, array $params = []): int {
    return (int) db_query($sql, $params)->fetchColumn();
}
