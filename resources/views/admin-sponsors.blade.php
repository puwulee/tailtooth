<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>贊助商管理 · Tailtooth</title>
    <style>
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid #ffcb45; font-weight:900; }
        .wrap { max-width:720px; margin:0 auto; padding:18px; }
        .card { background:#141a28; border:1px solid #26304a; border-radius:14px; padding:16px; margin-bottom:16px; }
        .add { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        input, select { padding:9px; border-radius:8px; border:1px solid #26304a; background:#1b2233; color:#eef2ff; }
        button { padding:9px 14px; border:0; border-radius:8px; font-weight:700; background:#ffcb45; color:#1a1300; cursor:pointer; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
        .s { background:#1b2233; border:1px solid #26304a; border-radius:12px; padding:14px; text-align:center; }
        .s img { max-width:100%; height:60px; object-fit:contain; } .s .nm { font-weight:700; margin-top:8px; } .s .t { color:#ffcb45; font-size:12px; }
        .del { background:#3a1320; color:#ff7b94; margin-top:8px; font-size:12px; padding:4px 10px; }
    </style>
</head>
<body>
    <header>🤝 贊助商管理</header>
    <div class="wrap">
        <div class="card">
            <div class="add">
                <input type="text" id="name" placeholder="贊助商名稱">
                <select id="tier"><option value="鑽石">鑽石</option><option value="黃金">黃金</option><option value="白銀">白銀</option><option value="夥伴">夥伴</option></select>
                <input type="file" id="logo" accept="image/*">
                <button onclick="add()">＋ 新增贊助商</button>
            </div>
            <div class="grid" id="list"></div>
        </div>
    </div>
    <script>
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    async function load() {
        const list = await (await fetch('/api/admin/sponsors',{headers:{Accept:'application/json'}})).json();
        document.getElementById('list').innerHTML = list.map(s => `<div class="s">
            ${s.logo?`<img src="${s.logo}">`:'<div style="height:60px"></div>'}
            <div class="nm">${esc(s.name)}</div><div class="t">${esc(s.tier)||''}</div>
            <button class="del" onclick="del(${s.id})">移除</button></div>`).join('') || '<div style="color:#6b7693">尚無贊助商</div>';
    }
    async function add() {
        const name = document.getElementById('name').value; if (!name) return alert('請輸入名稱');
        const fd = new FormData(); fd.append('name', name); fd.append('tier', document.getElementById('tier').value);
        const f = document.getElementById('logo').files[0]; if (f) fd.append('logo', f);
        const r = await fetch('/api/admin/sponsors',{method:'POST',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},body:fd});
        if (r.ok) { document.getElementById('name').value=''; load(); } else alert('新增失敗');
    }
    async function del(id) {
        if (!confirm('確定移除？')) return;
        const r = await fetch(`/api/admin/sponsors/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'}});
        if (r.ok) load();
    }
    load();
    </script>
</body>
</html>
