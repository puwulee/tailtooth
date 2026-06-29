<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>報名管理 · {{ $name }}</title>
    <style>
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 22px; border-bottom:2px solid #ffcb45; font-weight:900; }
        .wrap { max-width:900px; margin:0 auto; padding:18px; }
        table { width:100%; border-collapse:collapse; }
        th,td { text-align:left; padding:10px 8px; border-bottom:1px solid #26304a; font-size:14px; }
        th { color:#9aa6c4; font-size:12px; }
        .badge { font-size:12px; padding:2px 8px; border-radius:999px; background:#22304a; }
        .badge.paid { background:#1e3a2a; color:#5dffa0; } .badge.checked_in { background:#10381f; color:#5dffa0; }
        .badge.waitlisted { background:#3a3014; color:#ffcb45; } .badge.refunded,.badge.pending_payment { background:#3a1320; color:#ff7b94; }
        button { padding:6px 11px; border:0; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; margin-right:6px; }
        .ci { background:#10381f; color:#5dffa0; } .rf { background:#3a1320; color:#ff7b94; }
    </style>
</head>
<body data-tournament="{{ $tournamentId }}">
    <header>📋 {{ $name }} · 報名管理</header>
    <div class="wrap">
        <table><thead><tr><th>#</th><th>選手</th><th>組別</th><th>狀態</th><th>發票</th><th>繳費時間</th><th>操作</th></tr></thead>
        <tbody id="rows"></tbody></table>
    </div>
    <script>
    const tid = document.body.dataset.tournament;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    const label = {pending_payment:'待付款',paid:'已付款',checked_in:'已報到',waitlisted:'候補',refunded:'已退費'};
    async function load() {
        const rows = await (await fetch(`/api/admin/registrations/${tid}`,{headers:{Accept:'application/json'}})).json();
        document.getElementById('rows').innerHTML = rows.map(r => `<tr>
            <td>${r.id}</td><td>${esc(r.player)}</td><td>${esc(r.division)}</td>
            <td><span class="badge ${r.status}">${label[r.status]||r.status}</span></td>
            <td>${esc(r.invoice)||'—'}</td><td>${esc(r.paid_at)||'—'}</td>
            <td>${r.status==='paid'?`<button class="ci" onclick="act(${r.id},'checkin')">報到</button>`:''}
                ${(r.status==='paid'||r.status==='checked_in')?`<button class="rf" onclick="act(${r.id},'refund')">退費</button>`:''}</td>
            </tr>`).join('');
    }
    async function act(id, what) {
        if (what==='refund' && !confirm('確定退費並開立折讓單？')) return;
        const r = await fetch(`/api/admin/registrations/${id}/${what}`,{method:'POST',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'}});
        if (r.ok) load(); else alert('操作失敗');
    }
    load();
    </script>
</body>
</html>
