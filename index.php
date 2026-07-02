<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = '首頁';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <span class="badge">🇹🇭 Bangkok · One Stop Service</span>
        <h1>泰國投資設立公司 · BOI 申請<br>一站式專業代辦服務</h1>
        <p class="lead">H.G.M. (Thailand) Co., Ltd. 提供法律、財務、稅務諮詢，專業辦理泰國公司註冊、BOI 投資優惠、工作證與簽證申辦，協助您在泰國順利落地。</p>
        <div class="hero-actions">
            <a class="btn gold" href="<?= BASE_URL ?>/company-registration.php">開始公司註冊申辦 →</a>
            <a class="btn ghost" href="<?= BASE_URL ?>/boi-application.php">BOI 時程與費用試算</a>
        </div>
    </div>
</section>

<!-- 兩大線上工具 -->
<section class="block">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Online Tools</div>
            <h2>兩大線上申辦工具</h2>
            <p class="muted">讓客戶自助備齊資料、掌握申請時程與預算，加速在泰國的商務布局。</p>
        </div>
        <div class="tool-cards">
            <div class="tool-card">
                <span class="num">TOOL 01</span>
                <h3>🏢 泰國公司註冊 · 資料檢核與上傳</h3>
                <p>依照泰國成立公司所需的 10 項資料，逐項確認與上傳文件，系統即時檢核完成度，確保送件前資料齊全，避免來回補件。</p>
                <a class="btn" href="<?= BASE_URL ?>/company-registration.php">前往資料檢核 →</a>
            </div>
            <div class="tool-card">
                <span class="num">TOOL 02</span>
                <h3>📈 BOI 申請 · 時程表與費用試算</h3>
                <p>參考 BOI 申請各階段工作時程（約 135 個工作天），並依註冊資本、工作證數量與加購服務，快速估算整體預算與付款排程。</p>
                <a class="btn" href="<?= BASE_URL ?>/boi-application.php">前往時程試算 →</a>
            </div>
        </div>
    </div>
</section>

<!-- 服務項目 -->
<section class="block" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">One Stop Service</div>
            <h2>一站式服務項目</h2>
        </div>
        <div class="grid cols-3">
            <?php foreach ($GLOBALS['SERVICES'] as $s): ?>
                <div class="card">
                    <div class="icon"><?= $s['icon'] ?></div>
                    <h3><?= e($s['title']) ?></h3>
                    <div class="en"><?= e($s['en']) ?></div>
                    <p class="muted"><?= e($s['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="text-align:center;margin-top:1.6rem;">
            <a class="btn ghost" href="<?= BASE_URL ?>/services.php">查看完整服務說明 →</a>
        </p>
    </div>
</section>

<!-- 公司簡介 -->
<section class="block">
    <div class="container two-col">
        <div>
            <div class="eyebrow">About Us</div>
            <h2>關於 H.G.M. (Thailand)</h2>
            <p>我們是位於曼谷的專業商務顧問公司，深耕泰國市場，服務來自台灣、中國大陸與各地的投資人。從公司命名、股東架構規劃、註冊送件，到 BOI 投資優惠、工作證與簽證、會計稅務申報，提供完整的一站式服務。</p>
            <p class="muted">無論您是首次進入泰國市場，或需要協助辦理 BOI 藍皮書身份，我們的專任法律、財務與稅務顧問團隊都能協助您順利完成。</p>
        </div>
        <div class="card">
            <h3 style="margin-top:0">聯絡資訊</h3>
            <p class="muted" style="margin:.2rem 0"><?= e($GLOBALS['COMPANY']['address']) ?></p>
            <ul class="plain" style="margin-top:.8rem">
                <?php foreach ($GLOBALS['COMPANY']['phones'] as $label => $num): ?>
                    <li><strong><?= e($label) ?>：</strong><?= e($num) ?></li>
                <?php endforeach; ?>
                <?php foreach ($GLOBALS['COMPANY']['emails'] as $mail): ?>
                    <li>✉️ <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
