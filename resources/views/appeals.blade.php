<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>申訴審理 · Tailtooth</title>
    <style>
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid #ffcb45; font-weight:900; }
        .wrap { max-width:760px; margin:0 auto; padding:16px; }
        .a { background:#141a28; border:1px solid #26304a; border-radius:14px; padding:16px; margin-bottom:14px; }
        .meta { color:#9aa6c4; font-size:13px; } .reason { margin:8px 0; }
        button { padding:9px 14px; border:0; border-radius:10px; font-weight:700; margin-right:8px; }
        .up { background:#10381f; color:#5dffa0; } .rej { background:#3a1320; color:#ff7b94; }
        .empty { color:#6b7693; }
    </style>
</head>
<body>
    <header>⚖️ 申訴審理台</header>
    <div class="wrap"><div id="list"></div></div>
    <script>
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    async function load() {
        const items = await (await fetch('/api/appeals?status=open',{headers:{Accept:'application/json'}})).json();
        document.getElementById('list').innerHTML = items.length ? items.map(a => `
            <div class="a" data-id="${a.id}">
              <div class="meta">對戰 #${a.battle_id}　申訴人：${esc(a.player)}</div>
              <div class="reason">${esc(a.reason)}</div>
              <button class="up" data-d="uphold">✔ 成立（重判）</button>
              <button class="rej" data-d="reject">✘ 駁回（維持）</button>
            </div>`).join('') : '<div class="empty">目前沒有待審申訴</div>';
        document.querySelectorAll('button[data-d]').forEach(b => b.addEventListener('click', rule));
    }
    async function rule(e) {
        const id = e.target.closest('.a').dataset.id;
        const ruling = prompt('裁決說明（可空白）：') || '';
        const r = await fetch(`/api/appeals/${id}/rule`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},body:JSON.stringify({decision:e.target.dataset.d, ruling})});
        if (r.ok) load(); else alert('裁決失敗');
    }
    load();
    </script>
</body>
</html>
