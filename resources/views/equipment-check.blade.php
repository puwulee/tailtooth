<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>裝備驗規 · {{ $name }}</title>
    <style>
        body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        header { padding:16px 20px; border-bottom:2px solid #ffcb45; font-weight:900; }
        .wrap { max-width:760px; margin:0 auto; padding:16px; }
        .p { background:#141a28; border:1px solid #26304a; border-radius:14px; padding:14px; margin-bottom:14px; }
        .p h3 { margin:0 0 10px; font-size:16px; }
        .bey { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px dashed #26304a; }
        .auth { color:#9aa6c4; font-size:12px; }
        button { padding:7px 12px; border:0; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; margin-left:6px; }
        .ok { background:#10381f; color:#5dffa0; } .no { background:#3a1320; color:#ff7b94; }
        .ok.active { outline:2px solid #5dffa0; } .no.active { outline:2px solid #ff7b94; }
    </style>
</head>
<body data-div="{{ $divisionId }}">
    <header>🔧 裝備驗規 · {{ $name }}</header>
    <div class="wrap"><div id="list"></div></div>
    <script>
    const did = document.body.dataset.div;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const esc = s => (s ?? '').toString().replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    async function load() {
        const rows = await (await fetch(`/api/equipment/${did}/data`,{headers:{Accept:'application/json'}})).json();
        document.getElementById('list').innerHTML = rows.length ? rows.map(r => `
            <div class="p"><h3>${esc(r.player)}</h3>${r.beyblades.map(b => `
              <div class="bey">
                <span>${esc(b.name)} <span class="auth">${esc(b.authenticity)}</span></span>
                <span>
                  <button class="ok ${b.passed===true?'active':''}" onclick="rec(${r.registration_id},${b.id},true,this)">通過</button>
                  <button class="no ${b.passed===false?'active':''}" onclick="rec(${r.registration_id},${b.id},false,this)">不通過</button>
                </span></div>`).join('')}</div>`).join('') : '<div class="auth">尚無已報到選手</div>';
    }
    async function rec(reg, bey, passed, el) {
        const r = await fetch('/api/equipment/record',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,Accept:'application/json'},
            body: JSON.stringify({registration_id:reg, beyblade_id:bey, passed})});
        if (r.ok) { el.parentElement.querySelectorAll('button').forEach(b=>b.classList.remove('active')); el.classList.add('active'); }
        else alert('登錄失敗');
    }
    load();
    </script>
</body>
</html>
