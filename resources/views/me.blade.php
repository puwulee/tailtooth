<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>我的後台 · Tailtooth</title>
    <style>
        :root { --gold:#ffcb45; --line:#26304a; --card:#141a28; --ok:#5dffa0; --warn:#ffcb45; }
        * { box-sizing:border-box; } body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { display:flex; justify-content:space-between; align-items:center; padding:14px 22px; border-bottom:1px solid var(--line); }
        header .brand { font-weight:900; } header a { color:#9aa6c4; text-decoration:none; font-size:14px; }
        .wrap { max-width:820px; margin:0 auto; padding:20px; display:grid; gap:18px; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:20px; }
        .card h2 { margin:0 0 14px; font-size:17px; color:var(--gold); }
        .profile { display:flex; align-items:center; gap:16px; }
        .ava { width:84px; height:84px; border-radius:18px; background:#1b2233; object-fit:cover; border:1px solid var(--line); }
        .pname { font-size:20px; font-weight:800; } .psub { color:#9aa6c4; font-size:13px; }
        .file { font-size:13px; margin-top:8px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .bey { background:#1b2233; border:1px solid var(--line); border-radius:12px; padding:12px; text-align:center; }
        .bey img, .bey .ph { width:100%; height:90px; object-fit:contain; border-radius:8px; background:#0e1320; display:flex; align-items:center; justify-content:center; color:#6b7693; font-size:12px; }
        .bey .nm { font-weight:700; margin-top:8px; font-size:14px; }
        .st { font-size:11px; padding:2px 8px; border-radius:999px; margin-top:6px; display:inline-block; }
        .st.done { background:#10381f; color:var(--ok); } .st.pending,.st.processing { background:#3a3014; color:var(--warn); }
        .reg { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed var(--line); font-size:14px; }
        .badge { font-size:12px; padding:2px 8px; border-radius:999px; background:#22304a; }
        .badge.paid,.badge.checked_in { background:#10381f; color:var(--ok); }
        input[type=file] { color:#9aa6c4; font-size:13px; } button, .btn { padding:9px 14px; border:0; border-radius:10px; font-weight:700; background:var(--gold); color:#1a1300; cursor:pointer; }
        .add { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:12px; }
        .add input[type=text], .add select { padding:8px; border-radius:8px; border:1px solid var(--line); background:#1b2233; color:#eef2ff; }
    </style>
</head>
<body data-player="{{ $player?->id }}">
    <header>
        <div class="brand">⚔️ TAILTOOTH · 我的後台</div>
        <div><a href="/">首頁</a>　<form method="POST" action="/logout" style="display:inline">@csrf<a href="#" onclick="this.closest('form').submit()">登出</a></form></div>
    </header>
    <div class="wrap">
        <div class="card">
            <h2>我的資料</h2>
            <div class="profile">
                <img class="ava" id="ava" src="{{ $player?->avatar_path ? asset('storage/'.$player->avatar_path) : '' }}" alt="">
                <div>
                    <div class="pname">{{ $player?->nickname ?: $player?->real_name ?: '參賽者' }}</div>
                    <div class="psub">{{ $player?->real_name }}　{{ $player?->phone }}</div>
                    <div class="file"><input type="file" id="avatarFile" accept="image/*"> <button onclick="uploadAvatar()">上傳大頭照</button></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>我的陀螺　<span class="psub">（賽前需登錄並完成照片處理才可出戰）</span></h2>
            <div class="grid" id="beyblades"></div>
            <div class="add">
                <input type="text" id="bname" placeholder="陀螺名稱">
                <select id="auth"><option value="official">正版</option><option value="replica">非正版</option></select>
                <input type="file" id="beyFile" accept="image/*">
                <button onclick="addBey()">＋ 登錄陀螺</button>
            </div>
        </div>

        <div class="card">
            <h2>我的報名</h2>
            <div id="regs"></div>
        </div>
    </div>
    <script>
    const pid = document.body.dataset.player;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));

    async function loadBey() {
        const list = await (await fetch('/api/me/beyblades', {headers:{Accept:'application/json'}})).json();
        document.getElementById('beyblades').innerHTML = list.length ? list.map(b => `
            <div class="bey">
              ${b.image ? `<img src="${b.image}">` : `<div class="ph">照片處理中…</div>`}
              <div class="nm">${esc(b.name)}</div>
              <div class="psub">${esc(b.authenticity)} · ${esc(b.generation)}</div>
              <span class="st ${b.photo_status}">${({done:'可出戰',pending:'待處理',processing:'處理中',failed:'失敗'})[b.photo_status]||b.photo_status}</span>
            </div>`).join('') : '<div class="psub">尚未登錄陀螺</div>';
    }
    async function loadRegs() {
        const list = await (await fetch('/api/me/registrations', {headers:{Accept:'application/json'}})).json();
        document.getElementById('regs').innerHTML = list.length ? list.map(r => `
            <div class="reg"><span><b>${esc(r.tournament)}</b>　${esc(r.division)}　${r.invoice?('發票 '+esc(r.invoice)):''}</span>
            <span class="badge ${r.status}">${({pending_payment:'待付款',paid:'已付款',checked_in:'已報到',waitlisted:'候補',refunded:'已退費'})[r.status]||r.status}</span></div>`).join('')
            : '<div class="psub">尚無報名紀錄</div>';
    }
    async function uploadAvatar() {
        const f = document.getElementById('avatarFile').files[0]; if (!f) return;
        const fd = new FormData(); fd.append('avatar', f);
        const r = await fetch(`/api/players/${pid}/avatar`, {method:'POST', headers:{'X-CSRF-TOKEN':token, Accept:'application/json'}, body: fd});
        const j = await r.json(); if (r.ok) document.getElementById('ava').src = '/storage/' + j.avatar_path.replace(/^.*storage\//,''); else alert('上傳失敗');
    }
    async function addBey() {
        const f = document.getElementById('beyFile').files[0]; const name = document.getElementById('bname').value;
        if (!f || !name) return alert('請填寫陀螺名稱並選擇照片');
        const fd = new FormData(); fd.append('name', name); fd.append('generation', 'beyblade_x');
        fd.append('authenticity', document.getElementById('auth').value); fd.append('photo', f);
        const r = await fetch(`/api/players/${pid}/beyblades`, {method:'POST', headers:{'X-CSRF-TOKEN':token, Accept:'application/json'}, body: fd});
        if (r.ok) { document.getElementById('bname').value=''; loadBey(); } else alert('登錄失敗');
    }
    loadBey(); loadRegs();
    </script>
</body>
</html>
