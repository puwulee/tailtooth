<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $name }} · 賽程編排</title>
    <style>
        :root { --gold:#ffcb45; --line:#26304a; --card:#141a28; --ok:#5dffa0; --warn:#ff7b94; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid var(--gold); font-size:20px; font-weight:900; }
        .wrap { max-width:1000px; margin:0 auto; padding:16px; }
        .div { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px; margin-bottom:16px; }
        .div h2 { margin:0 0 6px; font-size:18px; }
        .meta { color:#9aa6c4; font-size:13px; margin-bottom:12px; }
        .controls { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
        select, button { padding:9px 12px; border-radius:10px; border:1px solid var(--line); background:#1b2233; color:#eef2ff; font-weight:600; }
        button.primary { background:linear-gradient(160deg,#ffd86b,#e0a900); color:#1a1300; border:0; }
        .battles { margin-top:8px; }
        .b { display:flex; justify-content:space-between; padding:8px 10px; border-bottom:1px dashed var(--line); font-size:14px; }
        .stage-h { color:var(--gold); font-weight:800; margin:10px 0 4px; font-size:14px; }
        .conflict { color:var(--warn); font-size:13px; }
        .badge { font-size:11px; padding:2px 8px; border-radius:999px; background:#22304a; }
        .badge.drawn { background:#3a3014; color:var(--gold); }
        .links a { color:var(--gold); margin-right:14px; font-size:13px; }
    </style>
</head>
<body data-tournament="{{ $tournamentId }}">
    <header>🗂️ {{ $name }} · 賽程編排後台</header>
    <div class="wrap">
        <div class="links">
            <a href="/broadcast/{{ $tournamentId }}" target="_blank">▶ 直播 TV 版面</a>
            <a href="/board/{{ $tournamentId }}" target="_blank">📺 觀眾看板</a>
            <a href="/api/broadcast/{{ $tournamentId }}/archive" target="_blank">⬇ 匯出成績</a>
        </div>
        <div id="divisions"></div>
    </div>

    <script>
    const tid = document.body.dataset.tournament;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    let venues = [];

    async function post(url, body) {
        const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'}, body: JSON.stringify(body)});
        const j = await r.json().catch(()=>({}));
        if (!r.ok) throw new Error(j.message || ('HTTP ' + r.status));
        return j;
    }

    function venueOptions() {
        return '<option value="">（不指定場地）</option>' + venues.map(v => `<option value="${v.id}">${esc(v.name)}</option>`).join('');
    }

    async function load() {
        const d = await (await fetch(`/api/scheduling/${tid}/overview`, {headers:{Accept:'application/json'}})).json();
        venues = d.venues || [];
        document.getElementById('divisions').innerHTML = d.divisions.map(div => `
            <div class="div" data-div="${div.id}">
              <h2>${esc(div.name)}</h2>
              <div class="meta">可出戰選手：<b>${div.ready_players}</b> 人　已建立賽段：${div.stages.length}</div>
              <div class="controls">
                <select class="type">
                  <option value="group">分組賽</option>
                  <option value="playoff">複賽</option>
                  <option value="final">冠軍戰</option>
                </select>
                <select class="format">
                  <option value="round_robin">循環賽</option>
                  <option value="single_elim">單敗淘汰</option>
                </select>
                <select class="venue">${venueOptions()}</select>
                <button class="primary gen">產生賽程</button>
              </div>
              <div class="battles" id="battles-${div.id}"></div>
            </div>`).join('');

        d.divisions.forEach(div => loadBattles(div.id));
        document.querySelectorAll('.gen').forEach(btn => btn.addEventListener('click', onGenerate));
    }

    async function onGenerate(e) {
        const box = e.target.closest('.div');
        const id = box.dataset.div;
        try {
            await post(`/api/scheduling/divisions/${id}/generate`, {
                type: box.querySelector('.type').value,
                format: box.querySelector('.format').value,
                venue_id: box.querySelector('.venue').value || null,
            });
            await loadBattles(id);
        } catch (err) { alert('產生失敗：' + err.message); }
    }

    async function loadBattles(id) {
        const stages = await (await fetch(`/api/scheduling/divisions/${id}/battles`, {headers:{Accept:'application/json'}})).json();
        document.getElementById('battles-' + id).innerHTML = stages.map(s => `
            <div class="stage-h">${({group:'分組賽',playoff:'複賽',final:'冠軍戰'})[s.type]} · ${({round_robin:'循環賽',single_elim:'單敗淘汰'})[s.format]}</div>
            ${s.conflicts.length ? `<div class="conflict">⚠ 偵測到選手排程衝突：${s.conflicts.join(', ')}</div>` : ''}
            ${s.battles.map(b => `<div class="b">
                <span>${esc(b.a)||'(輪空)'} vs ${esc(b.b)||'(輪空)'}</span>
                <span>${b.venue ? `<span class="badge ${b.venue_drawn?'drawn':''}">${esc(b.venue)}${b.venue_drawn?' 抽選':''}</span>` : ''} <span class="badge">${b.status}</span></span>
              </div>`).join('')}`).join('') || '<div class="meta">尚未產生賽程</div>';
    }

    load();
    </script>
</body>
</html>
