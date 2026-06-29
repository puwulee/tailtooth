<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>裁判計分 · Tailtooth</title>
    <style>
        :root { --bg:#0b0e14; --card:#151a26; --line:#26304a; --a:#ff3b3b; --b:#2f7bff; --gold:#ffcb45; --txt:#eef2ff; }
        * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:var(--bg); color:var(--txt); }
        header { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid var(--line); }
        .brand { font-weight:800; letter-spacing:1px; }
        .net { font-size:12px; padding:3px 10px; border-radius:999px; }
        .net.on { background:#10381f; color:#5dffa0; }
        .net.off { background:#3a1320; color:#ff7b94; }
        .stream { aspect-ratio:16/9; width:100%; background:#000; border-bottom:1px solid var(--line); }
        .stream iframe { width:100%; height:100%; border:0; }
        .score { display:flex; align-items:stretch; }
        .side { flex:1; text-align:center; padding:18px 8px; }
        .side .num { font-size:64px; font-weight:900; line-height:1; }
        .side.a .num { color:var(--a); } .side.b .num { color:var(--b); }
        .vs { display:flex; align-items:center; padding:0 6px; color:#6b7693; font-weight:800; }
        .target { text-align:center; color:#8b94ac; font-size:13px; padding:4px 0 10px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:12px 16px; }
        .col h3 { margin:0 0 6px; text-align:center; font-size:14px; }
        button.fin { width:100%; padding:16px 6px; margin-bottom:10px; border:0; border-radius:14px; font-size:17px; font-weight:800; color:#fff; }
        .col.a button.fin { background:linear-gradient(160deg,#ff5a5a,#c81e1e); }
        .col.b button.fin { background:linear-gradient(160deg,#5a93ff,#1e54c8); }
        button.fin small { display:block; font-weight:600; opacity:.85; font-size:12px; }
        button.fin:disabled { opacity:.4; }
        .bar { display:flex; gap:10px; padding:0 16px 16px; }
        .bar button { flex:1; padding:12px; border-radius:12px; border:1px solid var(--line); background:var(--card); color:var(--txt); font-weight:700; }
        .winner { margin:0 16px 14px; padding:14px; border-radius:14px; text-align:center; background:#1d2436; border:1px solid var(--gold); color:var(--gold); font-weight:800; display:none; }
        .log { padding:0 16px 24px; }
        .log .row { display:flex; justify-content:space-between; border-bottom:1px dashed var(--line); padding:8px 2px; font-size:13px; color:#aeb6cf; }
        .pending { color:var(--gold); font-size:12px; }
    </style>
</head>
<body data-battle="{{ $battleId }}">
    <header>
        <div class="brand">⚔️ Tailtooth 裁判台</div>
        <div id="net" class="net off">連線中…</div>
    </header>

    <div id="streamWrap" class="stream" style="display:none"></div>

    <div class="score">
        <div class="side a"><div class="num" id="sa">0</div><div>A 選手</div></div>
        <div class="vs">VS</div>
        <div class="side b"><div class="num" id="sb">0</div><div>B 選手</div></div>
    </div>
    <div class="target">先達 <b id="ptw">4</b> 點獲勝　<span id="queued" class="pending"></span></div>

    <div id="winner" class="winner"></div>

    <div class="grid">
        <div class="col a">
            <h3>A 得分</h3>
            <button class="fin" data-side="a" data-finish="xtreme">極限勝 <small>Xtreme · 3 點</small></button>
            <button class="fin" data-side="a" data-finish="over">出場勝 <small>Over · 2 點</small></button>
            <button class="fin" data-side="a" data-finish="burst">爆裂勝 <small>Burst · 2 點</small></button>
            <button class="fin" data-side="a" data-finish="spin">持續力勝 <small>Spin · 1 點</small></button>
        </div>
        <div class="col b">
            <h3>B 得分</h3>
            <button class="fin" data-side="b" data-finish="xtreme">極限勝 <small>Xtreme · 3 點</small></button>
            <button class="fin" data-side="b" data-finish="over">出場勝 <small>Over · 2 點</small></button>
            <button class="fin" data-side="b" data-finish="burst">爆裂勝 <small>Burst · 2 點</small></button>
            <button class="fin" data-side="b" data-finish="spin">持續力勝 <small>Spin · 1 點</small></button>
        </div>
    </div>

    <div class="bar">
        <button id="draw">平手回合</button>
        <button id="refresh">重新整理</button>
    </div>

    <div class="log"><div id="rounds"></div></div>

    <script>
    // 離線優先的裁判計分 SPA：操作先進 localStorage 佇列，連線後自動補送（client_event_id 冪等）。
    const battleId = document.body.dataset.battle;
    const base = `/api/battles/${battleId}`;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const QKEY = `bey.queue.${battleId}`;
    const queue = JSON.parse(localStorage.getItem(QKEY) || '[]');
    let beyA = {{ $beybladeA ?? 'null' }}, beyB = {{ $beybladeB ?? 'null' }};

    const $ = id => document.getElementById(id);
    const saveQ = () => { localStorage.setItem(QKEY, JSON.stringify(queue)); render(); };
    const uid = () => `${battleId}-${Date.now()}-${Math.floor(Math.random()*1e6)}`;

    function render(state) {
        $('queued').textContent = queue.length ? `（離線待送 ${queue.length} 筆）` : '';
        if (!state) return;
        $('sa').textContent = state.score_a; $('sb').textContent = state.score_b;
        $('ptw').textContent = state.points_to_win;
        const done = state.status === 'finished' || state.status === 'confirmed';
        document.querySelectorAll('button.fin').forEach(b => b.disabled = done);
        if (done && state.winner_id) { $('winner').style.display='block'; $('winner').textContent = '🏆 勝負已分'; }
        if (state.stream_url) { $('streamWrap').style.display='block'; $('streamWrap').innerHTML = `<iframe src="${state.stream_url}" allow="autoplay; encrypted-media" allowfullscreen></iframe>`; }
        $('rounds').innerHTML = state.rounds.map(r =>
            `<div class="row"><span>第 ${r.sequence} 回合</span><span>${r.is_draw ? '平手' : (r.finish||'') + ' · ' + r.points + ' 點'}</span></div>`).join('');
    }

    async function api(path, body) {
        const res = await fetch(base + path, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
            body: JSON.stringify(body||{})
        });
        if (!res.ok) throw new Error('http ' + res.status);
        return res.json();
    }

    async function flush() {
        while (queue.length) {
            const job = queue[0];
            try { const state = await api(job.path, job.body); queue.shift(); saveQ(); render(state); }
            catch (e) { throw e; }
        }
    }

    async function sync() {
        try {
            await flush();
            const res = await fetch(base, {headers:{'Accept':'application/json'}});
            render(await res.json());
            $('net').className = 'net on'; $('net').textContent = '● 已連線';
        } catch (e) {
            $('net').className = 'net off'; $('net').textContent = '○ 離線（計分已暫存）';
        }
    }

    function enqueue(path, body) { queue.push({path, body:{...body, client_event_id: uid()}}); saveQ(); sync(); }

    document.querySelectorAll('button.fin').forEach(btn => btn.addEventListener('click', () => {
        const side = btn.dataset.side;
        enqueue('/rounds', {
            winner_side: side, finish: btn.dataset.finish,
            winner_beyblade_id: side==='a'?beyA:beyB, loser_beyblade_id: side==='a'?beyB:beyA,
        });
    }));
    $('draw').addEventListener('click', () => enqueue('/draw', {}));
    $('refresh').addEventListener('click', sync);
    window.addEventListener('online', sync);
    sync(); setInterval(sync, 15000);
    </script>
</body>
</html>
