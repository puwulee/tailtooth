<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>後台設定 · Tailtooth</title>
    <style>
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid #ffcb45; font-weight:900; }
        .wrap { max-width:720px; margin:0 auto; padding:16px; }
        .grp { background:#141a28; border:1px solid #26304a; border-radius:14px; padding:16px; margin-bottom:16px; }
        .grp h2 { font-size:15px; color:#ffcb45; margin:0 0 12px; }
        .row { margin-bottom:12px; } label { font-size:13px; color:#9aa6c4; display:block; margin-bottom:4px; }
        input { width:100%; padding:10px; border-radius:10px; border:1px solid #26304a; background:#1b2233; color:#eef2ff; }
        .badge { font-size:11px; color:#5dffa0; } .badge.no { color:#ff7b94; }
        button { padding:12px 18px; border:0; border-radius:10px; font-weight:800; background:linear-gradient(160deg,#ffd86b,#e0a900); color:#1a1300; }
        .hint { color:#6b7693; font-size:12px; margin-top:4px; }
    </style>
</head>
<body>
    <header>🔑 後台設定 · API 金鑰</header>
    <div class="wrap">
        <p class="hint">秘密金鑰加密儲存、顯示遮罩；留空表示「不變更」。輸入後即時生效，無需改 .env。</p>
        <form id="form"></form>
        <button id="save">儲存設定</button>
    </div>
    <script>
    const token = document.querySelector('meta[name=csrf-token]').content;
    const groups = {payment:'金流（LINE Pay）', invoice:'電子發票（光貿）', line:'LINE 官方帳號推播', bg:'AI 去背服務'};
    async function load() {
        const fields = await (await fetch('/api/admin/settings',{headers:{Accept:'application/json'}})).json();
        const byGroup = {};
        fields.forEach(f => (byGroup[f.group] ??= []).push(f));
        document.getElementById('form').innerHTML = Object.entries(byGroup).map(([g, fs]) => `
            <div class="grp"><h2>${groups[g]||g}</h2>${fs.map(f => `
              <div class="row">
                <label>${f.label} ${f.configured?'<span class="badge">已設定</span>':'<span class="badge no">未設定</span>'}</label>
                <input name="${f.key}" placeholder="${f.is_secret ? (f.value||'輸入新值') : (f.value||'')}" ${f.is_secret?'type="password"':''}>
              </div>`).join('')}</div>`).join('');
    }
    document.getElementById('save').addEventListener('click', async () => {
        const settings = {};
        document.querySelectorAll('#form input').forEach(i => { if (i.value) settings[i.name] = i.value; });
        const r = await fetch('/api/admin/settings',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},body:JSON.stringify({settings})});
        alert(r.ok ? '已儲存' : '儲存失敗');
        if (r.ok) load();
    });
    load();
    </script>
</body>
</html>
