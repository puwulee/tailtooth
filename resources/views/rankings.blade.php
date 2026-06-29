<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>排行榜 · Tailtooth</title>
    <style>
        :root { --gold:#ffcb45; --line:#26304a; --card:#141a28; }
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:14px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; }
        header a { color:#9aa6c4; text-decoration:none; }
        .wrap { max-width:920px; margin:0 auto; padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        @media (max-width:760px){ .wrap{ grid-template-columns:1fr; } }
        .card { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:18px; }
        .card h2 { margin:0 0 12px; color:var(--gold); font-size:17px; }
        .row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px dashed var(--line); }
        .rank { width:26px; font-weight:900; color:var(--gold); text-align:center; }
        .ava { width:34px; height:34px; border-radius:9px; background:#1b2233; object-fit:cover; }
        .nm { flex:1; font-weight:700; } .nm a{ color:#eef2ff; text-decoration:none; }
        .pts { color:var(--gold); font-weight:800; }
        .combo { font-size:13px; } .combo .sig { color:#9aa6c4; font-size:12px; }
        .wr { color:#5dffa0; font-weight:700; }
    </style>
</head>
<body>
    <header><div style="font-weight:900">🏆 排行榜</div><a href="/">← 首頁</a></header>
    <div class="wrap">
        <div class="card"><h2>賽季積分榜</h2><div id="players"></div></div>
        <div class="card"><h2>最強陀螺組合榜</h2><div id="combos"></div></div>
    </div>
    <script>
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    async function load() {
        const d = await (await fetch('/api/rankings/data',{headers:{Accept:'application/json'}})).json();
        document.getElementById('players').innerHTML = d.players.length ? d.players.map((p,i) => `
            <div class="row"><div class="rank">${i+1}</div>
              ${p.avatar_path?`<img class="ava" src="/storage/${esc(p.avatar_path)}">`:'<div class="ava"></div>'}
              <div class="nm"><a href="/players/${p.player_id}">${esc(p.nickname||p.real_name)}</a></div>
              <div class="pts">${p.total_points} 分</div></div>`).join('') : '<div class="combo sig">尚無積分資料</div>';
        document.getElementById('combos').innerHTML = d.combos.length ? d.combos.map((c,i) => `
            <div class="row"><div class="rank">${i+1}</div>
              <div class="nm combo">${esc(c.combo_signature)}<div class="sig">${esc(c.generation)} · ${esc(c.authenticity)}</div></div>
              <div><span class="wr">${Math.round(c.win_rate*100)}%</span> <span class="sig">(${c.wins}/${c.appearances})</span></div></div>`).join('') : '<div class="combo sig">尚無對戰資料</div>';
    }
    load();
    </script>
</body>
</html>
