<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'BOI 申請時程與費用試算';
$activeNav = 'boi';

$timeline = boi_timeline();
$pricing  = boi_pricing();
$totalDays = array_sum(array_column($timeline, 'days'));

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>📈 BOI 申請 · 工作時程表與費用試算</h1>
        <p>掌握泰國投資促進委員會（BOI）申請各階段的工作時程與整體預算，快速評估申請計畫。</p>
    </div>
</section>

<!-- 時間表 -->
<section class="block">
    <div class="container">
        <div class="section-head" style="margin-bottom:1.4rem">
            <div class="eyebrow">BOI Application Timeline</div>
            <h2>申請 BOI 工作時程表</h2>
            <p class="muted">依 H.G.M.（泰國）作業流程，自討論填表至取得正式藍皮書身份，共計約
                <strong><?= $totalDays ?></strong> 個工作天。</p>
        </div>
        <div class="two-col">
            <div class="card">
                <div class="timeline">
                    <?php foreach ($timeline as $t): ?>
                        <div class="tl-item">
                            <div class="tl-title">
                                <?= sprintf('%d. ', $t['no']) ?><?= e($t['title']) ?>
                                <?php if ($t['days'] > 0): ?>
                                    <span class="tl-days">約 <?= $t['days'] ?> 個工作天</span>
                                <?php endif; ?>
                            </div>
                            <div class="tl-en"><?= e($t['en']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="notice" style="margin:0">
                    備註：實際時程會依客戶提供相關資訊與資料的進度而順延。上述工作天數為一般估算，僅供參考。
                </div>
            </div>
            <div class="card estimate-panel">
                <h3 style="margin-top:0">時程總覽</h3>
                <div class="est-line"><span>各階段工作天合計</span><span class="amt"><strong><?= $totalDays ?> 天</strong></span></div>
                <div class="est-line"><span>估計自然日（含假日）</span><span class="amt">約 <?= (int)round($totalDays * 1.4) ?> 天</span></div>
                <div class="est-line"><span>約略月份</span><span class="amt">約 <?= (int)ceil($totalDays * 1.4 / 30) ?> 個月</span></div>
                <p class="muted" style="font-size:.85rem;margin-top:.8rem">
                    ＊ 工作天不含週末及泰國國定假日；自然日為粗估換算。
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 費用試算 -->
<section class="block" style="background:#fff;border-top:1px solid var(--line)">
    <div class="container">
        <div class="section-head" style="margin-bottom:1.4rem">
            <div class="eyebrow">Cost Estimator</div>
            <h2>費用價格試算表</h2>
            <p class="muted">輸入註冊資本與需求，即時估算整體預算與建議付款排程。</p>
        </div>

        <div class="two-col" id="boiCalc"
             data-pricing='<?= e(json_encode($pricing, JSON_UNESCAPED_UNICODE)) ?>'
             data-days="<?= $totalDays ?>">
            <!-- 輸入區 -->
            <div class="card">
                <h3 style="margin-top:0">申請條件</h3>
                <div class="form-grid">
                    <div class="field">
                        <label>註冊資本金（泰銖 THB）</label>
                        <input type="number" id="capital" min="0" step="100000" value="2000000">
                    </div>
                    <div class="field">
                        <label>外籍工作證＋簽證（人數）</label>
                        <input type="number" id="permits" min="0" step="1" value="2">
                    </div>
                </div>

                <h4>基本服務</h4>
                <div class="checkbox-list">
                    <?php foreach ($pricing['base'] as $k => $item): ?>
                        <label>
                            <input type="checkbox" id="base_<?= e($k) ?>" checked <?= $k === 'company_registration' ? 'disabled' : '' ?>>
                            <span><?= e($item['label']) ?></span>
                            <span class="muted" style="margin-left:auto"><?= thb((float)$item['fee']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <h4 style="margin-top:1.2rem">加購服務</h4>
                <div class="checkbox-list">
                    <?php foreach ($pricing['addons'] as $k => $item): ?>
                        <label>
                            <input type="checkbox" id="addon_<?= e($k) ?>">
                            <span><?= e($item['label']) ?></span>
                            <span class="muted" style="margin-left:auto"><?= thb((float)$item['fee']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 結果區 -->
            <div class="estimate-panel">
                <div class="card">
                    <h3 style="margin-top:0">費用估算明細</h3>
                    <div id="estLines"></div>
                    <div id="paySchedule" class="pay-schedule"></div>
                    <input type="hidden" id="estTotalHidden" value="0">
                </div>

                <div class="card" style="margin-top:1.2rem">
                    <h3 style="margin-top:0">索取正式報價</h3>
                    <p class="muted" style="font-size:.9rem">送出後我們的顧問將依您的試算內容提供正式報價單。</p>
                    <form id="estimateSaveForm">
                        <div class="field"><label>聯絡人 <span class="req">*</span></label><input type="text" id="sv_name" required></div>
                        <div class="field"><label>電子郵件 <span class="req">*</span></label><input type="email" id="sv_email" required></div>
                        <div class="field"><label>聯絡電話</label><input type="text" id="sv_phone"></div>
                        <button class="btn gold" type="submit">儲存試算並索取報價 →</button>
                    </form>
                    <div id="saveMsg"></div>
                </div>
            </div>
        </div>

        <div class="notice" style="margin-top:1.5rem">
            ⚠️ 免責聲明：本試算表所列金額為系統預設參考值（單位：泰銖），實際費用將依公司型態、營業項目、資本額、BOI 類別與政府規費調整，
            <strong>最終以本公司正式報價單為準</strong>。政府規費僅為概估，不含第三方（會計師、翻譯、公證）實支費用。
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
