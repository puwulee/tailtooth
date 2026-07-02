<?php
/**
 * 資料庫連線（PDO / MySQL）
 * Returns a shared PDO instance. On failure the caller receives null so the
 * public marketing pages can still render without a database.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): ?PDO
{
    static $pdo = null;
    static $tried = false;

    if ($tried) {
        return $pdo;
    }
    $tried = true;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[HGM] DB connection failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}

/**
 * 需要資料庫的頁面呼叫此函式；若連不上會顯示友善訊息並終止。
 */
function require_db(): PDO
{
    $pdo = db();
    if ($pdo === null) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:32px;'
           . 'border:1px solid #e2e8f0;border-radius:12px;line-height:1.7">'
           . '<h2 style="color:#1e3a8a">資料庫尚未設定</h2>'
           . '<p>此功能需要 MySQL 資料庫。請先建立資料庫並匯入 <code>sql/schema.sql</code>，'
           . '再於 <code>config/config.php</code>（或環境變數）設定連線資訊。</p>'
           . '<pre style="background:#f1f5f9;padding:16px;border-radius:8px;overflow:auto">'
           . "mysql -u root -p &lt; sql/schema.sql</pre>"
           . '<p><a href="' . BASE_URL . '/index.php">← 返回首頁</a></p>'
           . '</div>';
        exit;
    }
    return $pdo;
}
