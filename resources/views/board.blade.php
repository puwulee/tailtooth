<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} · 觀眾看板</title>
    <style>
        :root { --gold:#ffcb45; --a:#ff3b3b; --b:#2f7bff; --line:#26304a; --card:#141a28; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid var(--gold); font-size:22px; font-weight:900; }
        .wrap { padding:16px; display:grid; gap:16px; max-width:900px; margin:0 auto; }
        h2 { color:var(--gold); font-size:17px; margin:8px 0; }
        .match { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; display:flex; align-items:center; gap:12px; }
        .match.live { border-color:var(--gold); }
        .nm { flex:1; font-weight:800; }
        .sc { font-size:30px; font-weight:900; }
        .sc.a { color:var(--a); } .sc.b { color:var(--b); }
        .tag { color:#9aa6c4; font-size:13px; }
        .win { color:var(--gold); }
    </style>
</head>
<body data-tournament="{{ $tournamentId }}">
    <header>📺 {{ $name }} · 觀眾看板</header>
    <div class="wrap">
        <div><h2>🔥 進行中</h2><div id="live"></div></div>
        <div><h2>📋 即將開始</h2><div id="upcoming"></div></div>
        <div><h2>🏆 最新戰果</h2><div id="recent"></div></div>
    </div>
    <script>
    const tid = document.body.dataset.tournament;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    const card = (b, live) => `<div class="match ${live?'live':''}">
        <div class="nm">${esc(b.a.name)||'A'}</div><div class="sc a">${b.a.score}</div>
        <div class="tag">vs</div><div class="sc b">${b.b.score}</div><div class="nm" style="text-align:right">${esc(b.b.name)||'B'}</div></div>`;
    const done = b => `<div class="match"><div class="nm">${esc(b.a.name)||'A'} vs ${esc(b.b.name)||'B'}</div>
        <div class="win">${b.a.score} : ${b.b.score}</div></div>`;
    async function tick() {
        try {
            const d = await (await fetch(`/api/broadcast/${tid}/data`,{headers:{Accept:'application/json'}})).json();
            document.getElementById('live').innerHTML = d.live.map(b=>card(b,true)).join('') || '<div class="tag">目前無進行中對戰</div>';
            document.getElementById('upcoming').innerHTML = d.upcoming.map(b=>card(b,false)).join('') || '<div class="tag">—</div>';
            document.getElementById('recent').innerHTML = d.recent.map(done).join('') || '<div class="tag">—</div>';
        } catch(e) {}
    }
    tick(); setInterval(tick, 8000);
    </script>
</body>
</html>
