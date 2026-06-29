<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>報名 · {{ $tournament->name }}</title>
    <style>
        :root { --gold:#ffcb45; --line:#26304a; --card:#141a28; }
        * { box-sizing:border-box; } body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 22px; border-bottom:1px solid var(--line); } header a { color:#9aa6c4; text-decoration:none; font-size:14px; }
        .wrap { max-width:600px; margin:0 auto; padding:22px; }
        h1 { font-size:24px; } .meta { color:#9aa6c4; font-size:14px; margin-bottom:18px; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:22px; }
        label { font-size:13px; color:#9aa6c4; display:block; margin:12px 0 5px; }
        input, select { width:100%; padding:11px; border-radius:10px; border:1px solid var(--line); background:#1b2233; color:#eef2ff; }
        .ck { display:flex; align-items:flex-start; gap:8px; margin-top:12px; font-size:13px; color:#cdd5ef; }
        .ck input { width:auto; margin-top:3px; }
        .fee { margin:16px 0; padding:14px; border-radius:12px; background:#1b2233; display:flex; justify-content:space-between; }
        .fee b { color:var(--gold); font-size:22px; }
        button { width:100%; padding:14px; border:0; border-radius:12px; font-weight:800; background:linear-gradient(160deg,#ffd86b,#e0a900); color:#1a1300; margin-top:8px; }
        .kids { display:none; } .note { color:#6b7693; font-size:12px; margin-top:14px; }
        #ok { display:none; margin-top:14px; padding:14px; border-radius:12px; background:#10381f; color:#5dffa0; }
    </style>
</head>
<body>
    <header><a href="/">← 返回首頁</a></header>
    <div class="wrap">
        <h1>{{ $tournament->name }}</h1>
        <div class="meta">📅 {{ $tournament->event_date?->format('Y/m/d') }}　🌀 {{ $tournament->generation->label() }}</div>
        <div class="card">
            <label>報名組別</label>
            <select id="division">
                @foreach ($tournament->divisions as $d)
                <option value="{{ $d->id }}" data-fee="{{ (int) $d->fee }}" data-kids="{{ $d->age_group->value === 'kids' ? 1 : 0 }}">{{ $d->name }}（{{ $d->age_group->label() }}）</option>
                @endforeach
            </select>
            <label>真實姓名</label><input id="real_name" placeholder="王小明">
            <label>暱稱 / 戰隊名</label><input id="nickname" placeholder="藍焰">
            <label>聯絡電話</label><input id="phone" placeholder="0912-345-678">
            <label>生日</label><input id="birthdate" type="date">

            <div class="kids" id="kids">
                <label>監護人姓名</label><input id="guardian_name">
                <label>監護人電話</label><input id="guardian_phone">
                <div class="ck"><input type="checkbox" id="guardian_consent"><span>本人為參賽兒童之監護人，同意其參賽（兒童組必填）</span></div>
            </div>

            <label>電子發票載具 / 統編 / 捐贈碼</label><input id="invoice_carrier" placeholder="/ABC1234">

            <div class="ck"><input type="checkbox" id="portrait_consent"><span>同意賽事拍攝之影像（含去背陀螺照）用於成績公告與社群宣傳</span></div>
            <div class="ck"><input type="checkbox" id="agree"><span>我已閱讀並同意競賽規則、退費政策與個資告知</span></div>

            <div class="fee"><span>應繳報名費</span><b id="fee">NT$0</b></div>
            <button id="submit">前往 LINE Pay 付款報名</button>
            <div id="ok"></div>
            <div class="note">送出後將導向 LINE Pay 完成付款，繳費成功即開立光貿電子發票並寄送確認。</div>
        </div>
    </div>
    <script>
    const token = document.querySelector('meta[name=csrf-token]').content;
    const sel = document.getElementById('division');
    function refresh() {
        const o = sel.selectedOptions[0];
        document.getElementById('fee').textContent = 'NT$' + o.dataset.fee;
        document.getElementById('kids').style.display = o.dataset.kids === '1' ? 'block' : 'none';
    }
    sel.addEventListener('change', refresh); refresh();
    document.getElementById('submit').addEventListener('click', async () => {
        if (!document.getElementById('agree').checked) return alert('請先勾選同意競賽規則與個資告知');
        const v = id => document.getElementById(id).value;
        const body = {
            real_name: v('real_name'), nickname: v('nickname'), phone: v('phone'), birthdate: v('birthdate') || null,
            guardian_name: v('guardian_name'), guardian_phone: v('guardian_phone'),
            guardian_consent: document.getElementById('guardian_consent').checked,
            portrait_consent: document.getElementById('portrait_consent').checked,
            invoice_carrier: v('invoice_carrier'),
        };
        const r = await fetch(`/api/me/register/${sel.value}`, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'}, body: JSON.stringify(body)});
        const j = await r.json();
        const ok = document.getElementById('ok');
        ok.style.display = 'block';
        ok.textContent = r.ok ? `✔ 報名建立成功（#${j.registration_id}），交易序號 ${j.payment.transaction_id}` : ('✘ ' + (j.message || '報名失敗'));
    });
    </script>
</body>
</html>
