<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} · 選手檔案</title>
    <style>
        :root { --gold:#ffcb45; --line:#26304a; --card:#141a28; }
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:14px 22px; border-bottom:1px solid var(--line); } header a { color:#9aa6c4; text-decoration:none; }
        .wrap { max-width:680px; margin:0 auto; padding:20px; }
        .hero { display:flex; align-items:center; gap:18px; background:var(--card); border:1px solid var(--line); border-radius:18px; padding:22px; }
        .ava { width:96px; height:96px; border-radius:20px; background:#1b2233; object-fit:cover; }
        .pname { font-size:26px; font-weight:900; } .badges { margin-top:8px; }
        .badge { display:inline-block; background:#1b2233; border:1px solid var(--line); border-radius:999px; padding:4px 12px; margin:3px 4px 0 0; font-size:13px; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:18px 0; }
        .stat { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:14px; text-align:center; }
        .stat .v { font-size:24px; font-weight:900; color:var(--gold); } .stat .l { color:#9aa6c4; font-size:12px; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px; margin-top:12px; }
        .card h3 { margin:0 0 8px; font-size:15px; color:var(--gold); } .chip { display:inline-block; background:#1b2233; border:1px solid var(--line); border-radius:8px; padding:5px 10px; margin:3px; font-size:13px; }
    </style>
</head>
<body data-player="{{ $playerId }}">
    <header><a href="/rankings">← 排行榜</a></header>
    <div class="wrap">
        <div class="hero">
            <img class="ava" id="ava" src="">
            <div>
                <div class="pname" id="pname"></div>
                <div class="badges" id="badges"></div>
            </div>
        </div>
        <div class="stats" id="stats"></div>
        <div class="card"><h3>Finish 分布</h3><div id="finishes"></div></div>
        <div class="card"><h3>使用陀螺</h3><div id="beyblades"></div></div>
    </div>
    <script>
    const pid = document.body.dataset.player;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    const finishLabel = {spin:'持續力',over:'出場',burst:'爆裂',xtreme:'極限'};
    async function load() {
        const d = await (await fetch(`/api/players/${pid}/data`,{headers:{Accept:'application/json'}})).json();
        document.getElementById('ava').src = d.player.avatar || '';
        document.getElementById('pname').textContent = d.player.name;
        document.getElementById('badges').innerHTML = d.badges.map(b => `<span class="badge">${b.icon} ${esc(b.label)}</span>`).join('') || '<span class="badge">新秀</span>';
        document.getElementById('stats').innerHTML = [
            ['賽季積分', d.season_points], ['最佳名次', d.best_rank ? '第 '+d.best_rank+' 名' : '—'],
            ['勝率', Math.round(d.win_rate*100)+'%'], ['出賽', d.wins+d.losses+' 回合'],
        ].map(([l,v]) => `<div class="stat"><div class="v">${v}</div><div class="l">${l}</div></div>`).join('');
        document.getElementById('finishes').innerHTML = Object.entries(d.finishes).map(([k,v]) => `<span class="chip">${finishLabel[k]||k}：${v}</span>`).join('') || '—';
        document.getElementById('beyblades').innerHTML = d.beyblades.map(b => `<span class="chip">${esc(b.name)}（${esc(b.generation)}）</span>`).join('') || '—';
    }
    load();
    </script>
</body>
</html>
