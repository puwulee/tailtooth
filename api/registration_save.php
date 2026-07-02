<?php
/**
 * 工具一 API：儲存檢核項目（勾選確認 / 上傳檔案）、回傳完成度、驗證是否齊全。
 * action = confirm | upload | verify
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
if ($pdo === null) {
    json_response(['ok' => false, 'error' => '資料庫未連線'], 503);
}

$in     = read_input();
$action = $in['action'] ?? ($_POST['action'] ?? '');
$ref    = trim($in['ref'] ?? ($_POST['ref'] ?? ''));

if ($ref === '') {
    json_response(['ok' => false, 'error' => '缺少參考編號'], 400);
}

// 取得申請
$stmt = $pdo->prepare('SELECT * FROM registration_applications WHERE ref_code = ?');
$stmt->execute([$ref]);
$app = $stmt->fetch();
if (!$app) {
    json_response(['ok' => false, 'error' => '查無此申辦案'], 404);
}
$appId = (int)$app['id'];

$checklist = registration_checklist();
$validKeys = array_column($checklist, 'key');

/** 重新計算完成度 */
function progress_for(PDO $pdo, int $appId, array $checklist): array
{
    $stmt = $pdo->prepare('SELECT * FROM registration_documents WHERE application_id = ?');
    $stmt->execute([$appId]);
    $docs = [];
    foreach ($stmt->fetchAll() as $d) {
        $docs[$d['item_key']] = $d;
    }
    $reqTotal = 0; $reqDone = 0; $allDone = 0; $pending = [];
    foreach ($checklist as $item) {
        $d = $docs[$item['key']] ?? null;
        $done = $d && (int)$d['is_confirmed'] === 1 && (!$item['upload'] || !empty($d['file_path']));
        if ($done) { $allDone++; }
        if ($item['require']) {
            $reqTotal++;
            $done ? $reqDone++ : $pending[] = $item['title'];
        }
    }
    return [
        'all_total' => count($checklist),
        'all_done'  => $allDone,
        'req_total' => $reqTotal,
        'req_done'  => $reqDone,
        'pct'       => $reqTotal ? (int)round($reqDone / $reqTotal * 100) : 0,
        'ready'     => $reqDone === $reqTotal,
        'pending'   => $pending,
    ];
}

switch ($action) {
    case 'confirm':
        $key = $in['item_key'] ?? '';
        if (!in_array($key, $validKeys, true)) {
            json_response(['ok' => false, 'error' => '無效的項目'], 400);
        }
        $confirmed = !empty($in['confirmed']) ? 1 : 0;
        $stmt = $pdo->prepare(
            'UPDATE registration_documents SET is_confirmed = ? WHERE application_id = ? AND item_key = ?'
        );
        $stmt->execute([$confirmed, $appId, $key]);
        json_response(['ok' => true, 'progress' => progress_for($pdo, $appId, $checklist)]);
        break;

    case 'upload':
        $key = $_POST['item_key'] ?? '';
        if (!in_array($key, $validKeys, true)) {
            json_response(['ok' => false, 'error' => '無效的項目'], 400);
        }
        try {
            [$path, $orig] = handle_upload($_FILES['file'] ?? [], $ref, $key);
        } catch (Throwable $ex) {
            json_response(['ok' => false, 'error' => $ex->getMessage()], 400);
        }
        // 上傳成功即視為已備妥
        $stmt = $pdo->prepare(
            'UPDATE registration_documents
             SET file_path = ?, original_name = ?, is_confirmed = 1, uploaded_at = NOW()
             WHERE application_id = ? AND item_key = ?'
        );
        $stmt->execute([$path, $orig, $appId, $key]);
        json_response([
            'ok'       => true,
            'file'     => ['path' => BASE_URL . '/' . $path, 'name' => $orig],
            'progress' => progress_for($pdo, $appId, $checklist),
        ]);
        break;

    case 'verify':
        $p = progress_for($pdo, $appId, $checklist);
        if ($p['ready']) {
            $pdo->prepare("UPDATE registration_applications SET status = 'submitted' WHERE id = ?")
                ->execute([$appId]);
        }
        json_response(['ok' => true, 'progress' => $p]);
        break;

    default:
        json_response(['ok' => false, 'error' => '未知的動作'], 400);
}
