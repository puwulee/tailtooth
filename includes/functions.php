<?php
/**
 * 共用輔助函式 Helper functions
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/data.php';

/** HTML 跳脫 */
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** 產生對外參考編號，例如 HGM-20260702-AB12 */
function generate_ref(string $prefix): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = '';
    for ($i = 0; $i < 4; $i++) {
        $suffix .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return sprintf('%s-%s-%s', $prefix, date('Ymd'), $suffix);
}

/** JSON 回應並結束 */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** 讀取 JSON / 表單 POST 內容 */
function read_input(): array
{
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }
    return $_POST;
}

/** 泰銖金額格式化 */
function thb(float $n): string
{
    return '฿ ' . number_format($n, 0);
}

/**
 * 處理單一檔案上傳，回傳 [相對路徑, 原始檔名] 或拋出例外。
 */
function handle_upload(array $file, string $refCode, string $itemKey): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('未選擇檔案');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('上傳失敗（錯誤碼 ' . $file['error'] . '）');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('檔案超過 ' . (UPLOAD_MAX_BYTES / 1024 / 1024) . 'MB 上限');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_UPLOAD_EXT, true)) {
        throw new RuntimeException('不支援的檔案格式：' . $ext);
    }

    $dir = UPLOAD_DIR . '/' . preg_replace('/[^A-Za-z0-9\-]/', '', $refCode);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('無法建立上傳目錄');
    }

    $safeKey  = preg_replace('/[^a-z_]/', '', $itemKey);
    $fileName = $safeKey . '_' . date('His') . '.' . $ext;
    $target   = $dir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        // 供本機測試（CLI/內建伺服器）時 fallback
        if (!rename($file['tmp_name'], $target)) {
            throw new RuntimeException('無法儲存檔案');
        }
    }

    $relative = 'uploads/' . basename($dir) . '/' . $fileName;
    return [$relative, $file['name']];
}
