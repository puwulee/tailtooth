<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '服務項目';
$activeNav = 'services';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>一站式服務項目 · One Stop Service</h1>
        <p>從公司設立到營運合規，H.G.M. (Thailand) 提供完整的專業代辦與顧問服務。</p>
    </div>
</section>

<section class="block">
    <div class="container">
        <div class="grid cols-2">
            <?php foreach ($GLOBALS['SERVICES'] as $i => $s): ?>
                <div class="card">
                    <div class="icon"><?= $s['icon'] ?></div>
                    <h3><?= sprintf('%02d', $i + 1) ?> · <?= e($s['title']) ?></h3>
                    <div class="en"><?= e($s['en']) ?></div>
                    <p class="muted"><?= e($s['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="notice" style="margin-top:2rem">
            以下兩項為本站提供的線上自助工具，協助您加速申辦流程：
        </div>
        <div class="tool-cards">
            <div class="tool-card">
                <span class="num">TOOL 01</span>
                <h3>🏢 泰國公司註冊 · 資料檢核與上傳</h3>
                <p>逐項確認並上傳成立公司所需的 10 項資料，系統即時檢核完成度。</p>
                <a class="btn" href="<?= BASE_URL ?>/company-registration.php">前往 →</a>
            </div>
            <div class="tool-card">
                <span class="num">TOOL 02</span>
                <h3>📈 BOI 申請 · 時程與費用試算</h3>
                <p>掌握 BOI 各階段工作時程並試算整體預算與付款排程。</p>
                <a class="btn" href="<?= BASE_URL ?>/boi-application.php">前往 →</a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
