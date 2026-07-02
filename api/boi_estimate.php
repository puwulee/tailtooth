<?php
/**
 * 工具二 API：儲存 BOI 費用試算並回傳參考編號。
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
if ($pdo === null) {
    json_response(['ok' => false, 'error' => '資料庫未連線'], 503);
}

$in = read_input();

$name  = trim($in['contact_name'] ?? '');
$email = trim($in['contact_email'] ?? '');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => '請填寫聯絡人與正確的電子郵件'], 400);
}

$ref    = generate_ref('BOI');
$addons = $in['addons'] ?? [];
if (!is_array($addons)) { $addons = []; }

$stmt = $pdo->prepare(
    'INSERT INTO boi_estimates
        (ref_code, contact_name, contact_email, contact_phone,
         registered_capital, num_work_permits, addons_json, total_fee, timeline_days)
     VALUES (?,?,?,?,?,?,?,?,?)'
);
$stmt->execute([
    $ref,
    $name,
    $email,
    trim($in['contact_phone'] ?? ''),
    (int)($in['registered_capital'] ?? 0),
    (int)($in['num_work_permits'] ?? 0),
    json_encode($addons, JSON_UNESCAPED_UNICODE),
    (float)($in['total_fee'] ?? 0),
    (int)($in['timeline_days'] ?? 0),
]);

json_response(['ok' => true, 'ref' => $ref]);
