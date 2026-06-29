<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>{{ $name }} · 直播版面</title>
    <script src="https://js.pusher.com/8.4/pusher.min.js"></script>
    <style>
        /* 固定 1920×1080 16:9，等比縮放鋪滿任意螢幕（電視/投影） */
        :root { --gold:#ffcb45; --a:#ff3b3b; --b:#2f7bff; --line:#26304a; --card:#141a28; }
        * { margin:0; box-sizing:border-box; font-family:system-ui,"Noto Sans TC",sans-serif; }
        html,body { height:100%; background:#000; overflow:hidden; }
        #stage { width:1920px; height:1080px; transform-origin:top left; position:absolute; top:0; left:0;
                 background:radial-gradient(1200px 700px at 30% -10%, #1b2236, #0a0d15); color:#eef2ff; display:flex; flex-direction:column; }
        header { height:96px; display:flex; align-items:center; justify-content:space-between; padding:0 36px;
                 border-bottom:3px solid var(--gold); background:linear-gradient(90deg,#151b2b,#0c0f17); }
        header .title { font-size:40px; font-weight:900; letter-spacing:2px; }
        header .title .sub { display:block; font-size:16px; font-weight:600; color:#9aa6c4; letter-spacing:1px; }
        header .brand { text-align:right; font-size:18px; color:var(--gold); font-weight:800; }
        header .brand small { display:block; color:#8b94ac; font-weight:500; font-size:13px; }
        main { flex:1; display:flex; min-height:0; }
        /* 左：賽事場次 + 對戰組合 */
        .left { width:760px; padding:24px 28px; overflow:hidden; display:flex; flex-direction:column; gap:18px; border-right:2px solid var(--line); }
        .panel h2 { font-size:22px; color:var(--gold); margin-bottom:10px; display:flex; align-items:center; gap:8px; }
        .live-match { background:var(--card); border:2px solid var(--gold); border-radius:18px; padding:18px 20px; }
        .vs-row { display:flex; align-items:center; gap:14px; }
        .pl { flex:1; display:flex; align-items:center; gap:12px; }
        .pl.r { flex-direction:row-reverse; text-align:right; }
        .ava { width:64px; height:64px; border-radius:14px; background:#222a3d; object-fit:cover; }
        .pname { font-size:26px; font-weight:800; }
        .pscore { font-size:64px; font-weight:900; line-height:1; }
        .pscore.a { color:var(--a); } .pscore.b { color:var(--b); }
        .mid { color:#6b7693; font-weight:900; font-size:22px; }
        .tag { font-size:14px; color:#9aa6c4; }
        .list .item { display:flex; justify-content:space-between; padding:10px 12px; border-bottom:1px dashed var(--line); font-size:18px; }
        .list .item .names { font-weight:700; }
        .win { color:var(--gold); font-weight:800; }
        /* 右：直播訊號 */
        .right { flex:1; display:flex; flex-direction:column; }
        .stream { flex:1; background:#000; position:relative; }
        .stream iframe, .stream video { width:100%; height:100%; border:0; }
        .stream .ph { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:10px; color:#6b7693; }
        .intro { height:120px; padding:16px 28px; background:#0c0f17; border-top:2px solid var(--line); font-size:15px; color:#aeb6cf; line-height:1.5; }
        .intro b { color:var(--gold); }
    </style>
</head>
<body data-tournament="{{ $tournamentId }}">
    <div id="stage">
        <header>
            <div class="title">{{ $name }}<span class="sub" id="ev"></span></div>
            <div class="brand">⚔️ TAILTOOTH<small>戰鬥陀螺競賽平台</small></div>
        </header>
        <main>
            <div class="left">
                <div class="panel">
                    <h2>🔥 進行中對戰</h2>
                    <div id="liveBox"></div>
                </div>
                <div class="panel" style="flex:1; overflow:hidden;">
                    <h2>📋 即將開始 / 對戰組合</h2>
                    <div class="list" id="upcoming"></div>
                </div>
                <div class="panel">
                    <h2>🏆 最新戰果</h2>
                    <div class="list" id="recent"></div>
                </div>
            </div>
            <div class="right">
                <div class="stream" id="stream">
                    <div class="ph">📡 等待直播訊號…</div>
                </div>
                <div class="intro">
                    <b>關於本平台</b>：Tailtooth 是專為戰鬥陀螺打造的競賽平台 —
                    線上報名、選手與陀螺登錄、裁判即時計分（Beyblade X 點數制）、賽季積分與最強陀螺榜。
                    歡迎各路好手報名參戰，少年與成人皆有舞台。
                    <span id="sponsors" style="float:right"></span>
                </div>
            </div>
        </main>
    </div>

    <script>
    const tid = document.body.dataset.tournament;
    const reverb = {!! json_encode($reverb) !!};
    const stage = document.getElementById('stage');

    function fit() {
        const s = Math.min(window.innerWidth / 1920, window.innerHeight / 1080);
        stage.style.transform = `scale(${s})`;
        document.body.style.background = '#000';
    }
    window.addEventListener('resize', fit); fit();

    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    const avatar = a => a ? `<img class="ava" src="/storage/${esc(a)}">` : `<div class="ava"></div>`;

    function renderLive(b) {
        if (!b) return `<div class="tag">目前沒有進行中的對戰</div>`;
        return `<div class="live-match">
            <div class="tag">${esc(b.division)||''} ${b.venue?'· '+esc(b.venue):''}</div>
            <div class="vs-row" style="margin-top:8px">
              <div class="pl">${avatar(b.a.avatar)}<div><div class="pname">${esc(b.a.name)||'A'}</div></div></div>
              <div class="pscore a">${b.a.score}</div>
              <div class="mid">VS</div>
              <div class="pscore b">${b.b.score}</div>
              <div class="pl r">${avatar(b.b.avatar)}<div><div class="pname">${esc(b.b.name)||'B'}</div></div></div>
            </div></div>`;
    }
    const row = b => `<div class="item"><span class="names">${esc(b.a.name)||'A'} vs ${esc(b.b.name)||'B'}</span>
        <span>${b.status==='finished'||b.status==='confirmed' ? `<span class="win">${b.a.score}:${b.b.score}</span>` : esc(b.division)||''}</span></div>`;

    async function tick() {
        try {
            const d = await (await fetch(`/api/broadcast/${tid}/data`, {headers:{Accept:'application/json'}})).json();
            document.getElementById('ev').textContent = d.tournament.event_date || '';
            document.getElementById('liveBox').innerHTML = renderLive(d.live[0]);
            document.getElementById('upcoming').innerHTML = d.upcoming.map(row).join('') || '<div class="tag">—</div>';
            document.getElementById('recent').innerHTML = d.recent.map(row).join('') || '<div class="tag">—</div>';
            if (d.sponsors && d.sponsors.length) {
                document.getElementById('sponsors').innerHTML = '贊助：' + d.sponsors.map(s =>
                    s.logo ? `<img src="${esc(s.logo)}" style="height:28px;vertical-align:middle;margin-left:8px">` : `<b style="color:#ffcb45;margin-left:8px">${esc(s.name)}</b>`).join('');
            }

            // 直播訊號：場次 > 賽事 層級
            const url = (d.live[0] && d.live[0].stream_url) || d.tournament.stream_url;
            const box = document.getElementById('stream');
            if (url && box.dataset.url !== url) {
                box.dataset.url = url;
                box.innerHTML = `<iframe src="${esc(url)}" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            }
        } catch (e) { /* 保持畫面 */ }
    }
    // 即時推播（Reverb）；連不上則退回輪詢
    function connectRealtime() {
        if (!reverb.key || !window.Pusher) return;
        try {
            const p = new Pusher(reverb.key, {
                wsHost: reverb.host, wsPort: reverb.port, wssPort: reverb.port,
                forceTLS: reverb.scheme === 'https', enabledTransports: ['ws','wss'],
                cluster: 'mt1', disableStats: true,
            });
            p.subscribe('tournament.' + tid).bind('battle.updated', tick);
        } catch (e) {}
    }
    connectRealtime();
    tick(); setInterval(tick, 10000); // 推播為主，輪詢為備援
    </script>
</body>
</html>
