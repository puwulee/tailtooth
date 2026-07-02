<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '泰國公司註冊申辦';
$activeNav = 'registration';

$pdo = require_db();
$checklist = registration_checklist();

// ---------------------------------------------------------------------------
// 建立新申請
// ---------------------------------------------------------------------------
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name  = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '請填寫聯絡人與正確的電子郵件。';
    } else {
        $ref = generate_ref('HGM');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO registration_applications (ref_code, contact_name, contact_email, contact_phone)
             VALUES (?,?,?,?)'
        );
        $stmt->execute([$ref, $name, $email, $phone]);
        $appId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO registration_documents (application_id, item_key) VALUES (?,?)');
        foreach ($checklist as $item) {
            $ins->execute([$appId, $item['key']]);
        }
        $pdo->commit();
        header('Location: ' . BASE_URL . '/company-registration.php?ref=' . urlencode($ref));
        exit;
    }
}

// ---------------------------------------------------------------------------
// 載入既有申請
// ---------------------------------------------------------------------------
$app = null; $docs = [];
$ref = trim($_GET['ref'] ?? ($_POST['ref'] ?? ''));
if ($ref !== '') {
    $stmt = $pdo->prepare('SELECT * FROM registration_applications WHERE ref_code = ?');
    $stmt->execute([$ref]);
    $app = $stmt->fetch();
    if ($app) {
        $stmt = $pdo->prepare('SELECT * FROM registration_documents WHERE application_id = ?');
        $stmt->execute([$app['id']]);
        foreach ($stmt->fetchAll() as $d) {
            $docs[$d['item_key']] = $d;
        }
    } else {
        $error = '查無此參考編號：' . $ref;
    }
}

// 計算完成度
function checklist_progress(array $checklist, array $docs): array
{
    $requiredTotal = 0; $requiredDone = 0; $allDone = 0;
    foreach ($checklist as $item) {
        $d = $docs[$item['key']] ?? null;
        $done = $d && (int)$d['is_confirmed'] === 1 && (!$item['upload'] || !empty($d['file_path']));
        if ($done) { $allDone++; }
        if ($item['require']) {
            $requiredTotal++;
            if ($done) { $requiredDone++; }
        }
    }
    return [
        'all_total'    => count($checklist),
        'all_done'     => $allDone,
        'req_total'    => $requiredTotal,
        'req_done'     => $requiredDone,
        'pct'          => $requiredTotal ? (int)round($requiredDone / $requiredTotal * 100) : 0,
        'ready'        => $requiredDone === $requiredTotal,
    ];
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>🏢 泰國公司註冊 · 資料檢核與上傳</h1>
        <p>依泰國成立公司所需資料逐項確認上傳，系統即時檢核送件完成度（約需 3–5 個工作天完成核名與備件）。</p>
    </div>
</section>

<section class="block">
    <div class="container">
    <?php if ($error): ?>
        <div class="alert err"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$app): ?>
        <!-- 起始畫面：建立或查詢申請 -->
        <div class="grid cols-2">
            <div class="card">
                <h3 style="margin-top:0">開立新的申辦案</h3>
                <p class="muted">填寫聯絡資訊即可取得專屬參考編號，開始上傳與檢核資料。</p>
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="field">
                        <label>聯絡人 <span class="req">*</span></label>
                        <input type="text" name="contact_name" required>
                    </div>
                    <div class="field">
                        <label>電子郵件 <span class="req">*</span></label>
                        <input type="email" name="contact_email" required>
                    </div>
                    <div class="field">
                        <label>聯絡電話</label>
                        <input type="text" name="contact_phone" placeholder="含國碼，例如 +886 / +66">
                    </div>
                    <button class="btn gold" type="submit">建立申辦案 →</button>
                </form>
            </div>
            <div class="card">
                <h3 style="margin-top:0">已有參考編號？</h3>
                <p class="muted">輸入先前取得的參考編號，繼續補齊資料。</p>
                <form method="get">
                    <div class="field">
                        <label>參考編號</label>
                        <input type="text" name="ref" placeholder="HGM-20260702-AB12" required>
                    </div>
                    <button class="btn" type="submit">查詢並繼續 →</button>
                </form>
                <hr style="border:0;border-top:1px solid var(--line);margin:1.4rem 0">
                <h4>成立公司所需資料一覽</h4>
                <ol class="muted" style="padding-left:1.2rem;font-size:.92rem">
                    <?php foreach ($checklist as $item): ?>
                        <li><?= e($item['title']) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    <?php else:
        $prog = checklist_progress($checklist, $docs); ?>
        <!-- 案件檢核畫面 -->
        <div class="ref-box">
            <div>參考編號 REF：<code><?= e($app['ref_code']) ?></code></div>
            <div class="muted" style="color:#cdd7ec">聯絡人：<?= e($app['contact_name']) ?> · <?= e($app['contact_email']) ?></div>
        </div>

        <div class="progress-wrap" style="margin-top:1.2rem">
            <div class="progress-meta">
                <strong>送件資料完成度（必備項目）</strong>
                <span class="pct" id="pctLabel"><?= $prog['pct'] ?>%</span>
            </div>
            <div class="progress-bar"><span id="progressFill" style="width:<?= $prog['pct'] ?>%"></span></div>
            <div class="progress-meta">
                <span class="muted">必備項目 <span id="reqDone"><?= $prog['req_done'] ?></span>/<?= $prog['req_total'] ?> · 全部項目 <?= $prog['all_done'] ?>/<?= $prog['all_total'] ?></span>
                <span id="readyBadge">
                    <?php if ($prog['ready']): ?>
                        <span class="tag" style="background:#dcfce7;color:#14532d">✅ 資料齊全，可安排送件</span>
                    <?php else: ?>
                        <span class="tag" style="background:#fef3c7;color:#92400e">尚有必備資料待補齊</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="notice">
            勾選代表您確認該項資料已備妥；標示「需上傳」的項目請一併上傳檔案（PDF／圖片／Word／Excel，單檔上限 10MB）。
            所有變更即時儲存，可隨時關閉再以參考編號繼續。
        </div>

        <div class="checklist" id="checklist" data-ref="<?= e($app['ref_code']) ?>">
            <?php foreach ($checklist as $item):
                $d = $docs[$item['key']] ?? [];
                $confirmed = !empty($d['is_confirmed']);
                $hasFile = !empty($d['file_path']);
                $done = $confirmed && (!$item['upload'] || $hasFile);
            ?>
                <div class="check-item <?= $done ? 'done' : '' ?>" data-key="<?= e($item['key']) ?>" data-upload="<?= $item['upload'] ? '1' : '0' ?>" data-require="<?= $item['require'] ? '1' : '0' ?>">
                    <div class="ci-head">
                        <div class="ci-no"><?= $done ? '✓' : $item['no'] ?></div>
                        <div class="ci-body">
                            <h4>
                                <?= e($item['title']) ?>
                                <?php if ($item['require']): ?><span class="tag req">必備</span><?php else: ?><span class="tag opt">選填</span><?php endif; ?>
                                <?php if ($item['upload']): ?><span class="tag up">需上傳</span><?php endif; ?>
                            </h4>
                            <p class="ci-desc"><?= e($item['desc']) ?></p>

                            <div class="ci-actions">
                                <label class="chk">
                                    <input type="checkbox" class="confirm-box" <?= $confirmed ? 'checked' : '' ?>>
                                    我已備妥此項資料
                                </label>

                                <?php if ($item['upload']): ?>
                                    <form class="upload-form" method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/api/registration_save.php">
                                        <input type="hidden" name="action" value="upload">
                                        <input type="hidden" name="ref" value="<?= e($app['ref_code']) ?>">
                                        <input type="hidden" name="item_key" value="<?= e($item['key']) ?>">
                                        <input type="file" name="file" class="file-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                        <button type="submit" class="btn sm ghost">上傳</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <?php if ($item['upload']): ?>
                                <div class="file-current" style="margin-top:.5rem">
                                    <?php if ($hasFile): ?>
                                        📎 <a href="<?= BASE_URL . '/' . e($d['file_path']) ?>" target="_blank"><?= e($d['original_name'] ?: '已上傳檔案') ?></a>
                                    <?php else: ?>
                                        <span class="file-hint">尚未上傳檔案</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:1.6rem;display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
            <button class="btn gold" id="verifyBtn">✔ 檢驗資料是否完成</button>
            <a class="btn ghost" href="<?= BASE_URL ?>/company-registration.php">開立其他申辦案</a>
            <span id="verifyMsg"></span>
        </div>
    <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
