</main>
<footer class="site-footer" id="contact">
    <div class="container footer-grid">
        <div>
            <h3><?= e($GLOBALS['COMPANY']['name_en']) ?></h3>
            <p class="muted"><?= e($GLOBALS['COMPANY']['name_zh']) ?> · <?= e($GLOBALS['COMPANY']['name_th']) ?></p>
            <p class="muted">董事 <?= e($GLOBALS['COMPANY']['director']) ?><br>
               統一編號 Tax ID：<?= e($GLOBALS['COMPANY']['tax_id']) ?></p>
            <p class="muted"><?= e($GLOBALS['COMPANY']['address']) ?></p>
        </div>
        <div>
            <h4>聯絡電話</h4>
            <ul class="plain">
                <?php foreach ($GLOBALS['COMPANY']['phones'] as $label => $num): ?>
                    <li><span class="muted"><?= e($label) ?>：</span><?= e($num) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>電子郵件</h4>
            <ul class="plain">
                <?php foreach ($GLOBALS['COMPANY']['emails'] as $mail): ?>
                    <li><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
    </div>
</footer>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
