/* =========================================================================
   H.G.M. (Thailand) — 前端互動
   ========================================================================= */
(function () {
    'use strict';

    const API_REG = document.currentScript
        ? '' // placeholder; resolved below via data attribute
        : '';

    /* ----------------------------------------------------------------- */
    /* 工具一：公司註冊檢核清單                                            */
    /* ----------------------------------------------------------------- */
    const checklist = document.getElementById('checklist');
    if (checklist) {
        const ref = checklist.dataset.ref;
        const apiUrl = baseJoin('api/registration_save.php');

        function applyProgress(p) {
            if (!p) return;
            const fill = document.getElementById('progressFill');
            const pct = document.getElementById('pctLabel');
            const reqDone = document.getElementById('reqDone');
            const badge = document.getElementById('readyBadge');
            if (fill) fill.style.width = p.pct + '%';
            if (pct) pct.textContent = p.pct + '%';
            if (reqDone) reqDone.textContent = p.req_done;
            if (badge) {
                badge.innerHTML = p.ready
                    ? '<span class="tag" style="background:#dcfce7;color:#14532d">✅ 資料齊全，可安排送件</span>'
                    : '<span class="tag" style="background:#fef3c7;color:#92400e">尚有必備資料待補齊</span>';
            }
        }

        function refreshItemState(item) {
            const box = item.querySelector('.confirm-box');
            const needUpload = item.dataset.upload === '1';
            const hasFile = !!item.querySelector('.file-current a');
            const done = box && box.checked && (!needUpload || hasFile);
            item.classList.toggle('done', done);
            const no = item.querySelector('.ci-no');
            if (no) no.textContent = done ? '✓' : no.dataset.n || no.textContent;
        }

        // 記住原始編號
        checklist.querySelectorAll('.ci-no').forEach(function (n) {
            if (!/^\d+$/.test(n.textContent.trim())) return;
            n.dataset.n = n.textContent.trim();
        });

        // 勾選確認
        checklist.querySelectorAll('.confirm-box').forEach(function (box) {
            box.addEventListener('change', function () {
                const item = box.closest('.check-item');
                postJSON(apiUrl, {
                    action: 'confirm', ref: ref,
                    item_key: item.dataset.key, confirmed: box.checked
                }).then(function (res) {
                    if (res && res.ok) { applyProgress(res.progress); refreshItemState(item); }
                    else { box.checked = !box.checked; alert((res && res.error) || '儲存失敗'); }
                }).catch(function () { box.checked = !box.checked; alert('連線失敗'); });
            });
        });

        // 上傳檔案（AJAX）
        checklist.querySelectorAll('.upload-form').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                const input = form.querySelector('.file-input');
                if (!input.files.length) { alert('請先選擇檔案'); return; }
                const btn = form.querySelector('button');
                const oldText = btn.textContent;
                btn.disabled = true; btn.textContent = '上傳中…';
                fetch(form.action, { method: 'POST', body: new FormData(form) })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        btn.disabled = false; btn.textContent = oldText;
                        if (!res.ok) { alert(res.error || '上傳失敗'); return; }
                        const item = form.closest('.check-item');
                        let box = item.querySelector('.file-current');
                        if (!box) { box = document.createElement('div'); box.className = 'file-current'; item.querySelector('.ci-body').appendChild(box); }
                        box.innerHTML = '📎 <a href="' + res.file.path + '" target="_blank">' + escapeHtml(res.file.name) + '</a>';
                        const cb = item.querySelector('.confirm-box');
                        if (cb) cb.checked = true;
                        applyProgress(res.progress);
                        refreshItemState(item);
                        input.value = '';
                    })
                    .catch(function () { btn.disabled = false; btn.textContent = oldText; alert('連線失敗'); });
            });
        });

        // 檢驗是否完成
        const verifyBtn = document.getElementById('verifyBtn');
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function () {
                const msg = document.getElementById('verifyMsg');
                postJSON(apiUrl, { action: 'verify', ref: ref }).then(function (res) {
                    if (!res || !res.ok) { msg.innerHTML = '<span class="tag req">檢驗失敗</span>'; return; }
                    applyProgress(res.progress);
                    if (res.progress.ready) {
                        msg.innerHTML = '<span class="alert ok" style="display:inline-block;margin:0;padding:.4rem .8rem">🎉 所有必備資料已齊全，本公司將盡快與您聯繫安排送件。</span>';
                    } else {
                        const list = (res.progress.pending || []).map(escapeHtml).join('、');
                        msg.innerHTML = '<span class="alert err" style="display:inline-block;margin:0;padding:.4rem .8rem">尚缺：' + list + '</span>';
                    }
                });
            });
        }
    }

    /* ----------------------------------------------------------------- */
    /* 工具二：BOI 費用試算                                               */
    /* ----------------------------------------------------------------- */
    const calc = document.getElementById('boiCalc');
    if (calc) {
        const cfg = JSON.parse(calc.dataset.pricing);
        const fmt = function (n) { return '฿ ' + Math.round(n).toLocaleString('en-US'); };

        function compute() {
            const capital = Math.max(0, parseFloat(val('capital')) || 0);
            const permits = Math.max(0, parseInt(val('permits'), 10) || 0);

            const lines = [];
            let total = 0;

            // 基本服務費
            Object.keys(cfg.base).forEach(function (k) {
                const item = cfg.base[k];
                const on = document.getElementById('base_' + k);
                if (!on || on.checked) { lines.push([item.label, item.fee]); total += item.fee; }
            });

            // 政府規費（依資本）
            let govt = Math.round(capital * cfg.govt_fee_rate);
            govt = Math.min(Math.max(govt, cfg.govt_fee_min), cfg.govt_fee_max);
            if (capital > 0) { lines.push(['政府登記規費（依資本 ' + fmt(capital) + '）', govt]); total += govt; }

            // 工作證＋簽證
            if (permits > 0) {
                const wp = permits * cfg.work_permit_per_person;
                lines.push(['外籍工作證＋簽證 × ' + permits, wp]); total += wp;
            }

            // 加購
            Object.keys(cfg.addons).forEach(function (k) {
                const on = document.getElementById('addon_' + k);
                if (on && on.checked) { lines.push([cfg.addons[k].label, cfg.addons[k].fee]); total += cfg.addons[k].fee; }
            });

            // 輸出明細
            const out = document.getElementById('estLines');
            out.innerHTML = lines.map(function (l) {
                return '<div class="est-line"><span>' + escapeHtml(l[0]) + '</span><span class="amt">' + fmt(l[1]) + '</span></div>';
            }).join('') +
            '<div class="est-line total"><span>估算總費用</span><span class="amt">' + fmt(total) + '</span></div>';

            // 付款排程
            const pay = document.getElementById('paySchedule');
            pay.innerHTML = '<h4>建議付款排程</h4>' + cfg.payment_schedule.map(function (s) {
                return '<div class="est-line"><span>' + escapeHtml(s.label) + '（' + s.pct + '%）</span><span class="amt">' + fmt(total * s.pct / 100) + '</span></div>';
            }).join('');

            document.getElementById('estTotalHidden').value = Math.round(total);
        }

        function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }
        calc.addEventListener('input', compute);
        calc.addEventListener('change', compute);
        compute();

        // 儲存試算
        const saveForm = document.getElementById('estimateSaveForm');
        if (saveForm) {
            saveForm.addEventListener('submit', function (ev) {
                ev.preventDefault();
                const data = collectEstimate(cfg);
                postJSON(baseJoin('api/boi_estimate.php'), data).then(function (res) {
                    const msg = document.getElementById('saveMsg');
                    if (res && res.ok) {
                        msg.innerHTML = '<div class="alert ok">已儲存！您的試算參考編號：<strong>' + escapeHtml(res.ref) + '</strong>，我們的顧問將依此為您提供正式報價。</div>';
                        saveForm.reset();
                    } else {
                        msg.innerHTML = '<div class="alert err">' + ((res && res.error) || '儲存失敗') + '</div>';
                    }
                });
            });
        }

        function collectEstimate(cfg) {
            const addons = [];
            Object.keys(cfg.addons).forEach(function (k) {
                const on = document.getElementById('addon_' + k);
                if (on && on.checked) addons.push(cfg.addons[k].label);
            });
            return {
                contact_name: val('sv_name'),
                contact_email: val('sv_email'),
                contact_phone: val('sv_phone'),
                registered_capital: parseFloat(val('capital')) || 0,
                num_work_permits: parseInt(val('permits'), 10) || 0,
                addons: addons,
                total_fee: parseFloat(document.getElementById('estTotalHidden').value) || 0,
                timeline_days: parseInt(calc.dataset.days, 10) || 0
            };
        }
    }

    /* ----------------------------------------------------------------- */
    /* 共用工具                                                           */
    /* ----------------------------------------------------------------- */
    function baseJoin(path) {
        const base = (window.HGM_BASE || '').replace(/\/$/, '');
        return base + '/' + path;
    }
    function postJSON(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) { return r.json(); });
    }
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
})();
