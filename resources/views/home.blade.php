<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tailtooth · 戰鬥陀螺競賽平台</title>
    <style>
        :root { --gold:#ffcb45; --a:#ff3b3b; --b:#2f7bff; --line:#26304a; --card:#141a28; }
        * { box-sizing:border-box; } body { margin:0; font-family:system-ui,"Noto Sans TC",sans-serif; background:#0a0d15; color:#eef2ff; }
        .nav { display:flex; justify-content:space-between; align-items:center; padding:14px 22px; border-bottom:1px solid var(--line); }
        .nav .brand { font-weight:900; letter-spacing:1px; } .nav a { color:#cdd5ef; text-decoration:none; margin-left:18px; font-size:14px; font-weight:600; }
        .nav a.cta { color:#1a1300; background:var(--gold); padding:8px 14px; border-radius:10px; }
        .hero { padding:64px 22px; text-align:center; background:radial-gradient(900px 480px at 50% -20%, #20294180, transparent); }
        .hero h1 { font-size:46px; margin:0 0 10px; background:linear-gradient(90deg,#ff5a5a,#ffcb45,#2f7bff); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .hero p { color:#aeb6cf; max-width:620px; margin:0 auto 24px; line-height:1.7; }
        .btn { display:inline-block; padding:13px 26px; border-radius:12px; font-weight:800; text-decoration:none; margin:0 6px; }
        .btn.gold { background:linear-gradient(160deg,#ffd86b,#e0a900); color:#1a1300; } .btn.ghost { border:1px solid var(--line); color:#eef2ff; }
        .wrap { max-width:980px; margin:0 auto; padding:24px 22px; }
        h2.sec { color:var(--gold); border-left:4px solid var(--gold); padding-left:10px; }
        .ev { background:var(--card); border:1px solid var(--line); border-radius:16px; padding:22px; margin-bottom:18px; }
        .ev h3 { margin:0 0 6px; font-size:24px; } .ev .meta { color:#9aa6c4; font-size:14px; margin-bottom:14px; }
        .divs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .chip { background:#1b2233; border:1px solid var(--line); border-radius:999px; padding:6px 14px; font-size:13px; }
        .chip b { color:var(--gold); }
        .prizes { color:#cdd5ef; font-size:14px; } .prizes span { margin-right:14px; }
        .rules { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:10px; }
        .rule { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:14px; text-align:center; }
        .rule .pt { font-size:30px; font-weight:900; } .rule.x .pt{color:#ffcb45} .rule.o .pt{color:#ff3b3b} .rule.bu .pt{color:#b07bff} .rule.s .pt{color:#5dffa0}
        footer { color:#6b7693; text-align:center; padding:30px; border-top:1px solid var(--line); font-size:13px; }
    </style>
</head>
<body>
    <div class="nav">
        <div class="brand">⚔️ TAILTOOTH</div>
        <div>
            <a href="#events">{{ __('賽事') }}</a>
            <a href="/rankings">{{ __('排行榜') }}</a>
            <a href="/me">{{ __('我的後台') }}</a>
            <a href="?lang={{ app()->getLocale() === 'en' ? 'zh_TW' : 'en' }}">{{ app()->getLocale() === 'en' ? '中文' : 'EN' }}</a>
            <a class="cta" href="#events">{{ __('立即報名') }}</a>
        </div>
    </div>

    <div class="hero">
        <h1>{{ __('戰鬥陀螺競賽平台') }}</h1>
        <p>線上報名、選手與陀螺登錄、裁判即時計分、賽季積分與最強陀螺榜。<br>對齊 Beyblade X 官方點數制 — 少年與成人，皆有熱血舞台。</p>
        <a class="btn gold" href="#events">{{ __('瀏覽賽事') }}</a>
        <a class="btn ghost" href="/me">{{ __('我的後台') }}</a>
    </div>

    <div class="wrap" id="events">
        <h2 class="sec">{{ __('近期賽事') }}</h2>
        @foreach ($tournaments as $t)
        <div class="ev">
            <h3>{{ $t->name }}</h3>
            <div class="meta">📅 {{ $t->event_date?->format('Y/m/d') }}　🌀 {{ $t->generation->label() }}　狀態：{{ $t->status }}</div>
            <div class="divs">
                @foreach ($t->divisions as $d)
                <span class="chip">{{ $d->name }}　報名費 <b>NT${{ (int) $d->fee }}</b>　{{ $d->deck_size }} 顆制</span>
                @endforeach
            </div>
            @if ($t->prizes)
            <div class="prizes">🏆 @foreach ($t->prizes as $k => $v)<span>{{ $k }}：{{ $v }}</span>@endforeach</div>
            @endif
            <div style="margin-top:14px"><a class="btn gold" href="/events/{{ $t->id }}/register">我要報名</a>
                <a class="btn ghost" href="/board/{{ $t->id }}">即時戰況</a></div>
        </div>
        @endforeach

        <h2 class="sec" style="margin-top:30px">計分規則（Beyblade X）</h2>
        <div class="rules">
            <div class="rule x"><div class="pt">3</div>極限勝 Xtreme</div>
            <div class="rule o"><div class="pt">2</div>出場勝 Over</div>
            <div class="rule bu"><div class="pt">2</div>爆裂勝 Burst</div>
            <div class="rule s"><div class="pt">1</div>持續力勝 Spin</div>
        </div>
        <p style="color:#9aa6c4;font-size:14px">單場先達 <b style="color:#ffcb45">4 點</b>獲勝　·　3 顆一組（Deck）　·　回合上限 3 分鐘</p>

        @if ($sponsors->count())
        <h2 class="sec" style="margin-top:30px">贊助夥伴</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
            @foreach ($sponsors as $s)
            <div style="background:#141a28;border:1px solid #26304a;border-radius:12px;padding:14px 18px;text-align:center;min-width:120px">
                @if ($s->logo_path)<img src="{{ asset('storage/'.$s->logo_path) }}" style="height:44px;object-fit:contain"><br>@endif
                <span style="font-weight:700">{{ $s->name }}</span>
                @if ($s->tier)<div style="color:#ffcb45;font-size:12px">{{ $s->tier }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <footer>© 2026 Tailtooth 戰鬥陀螺競賽平台　·　LINE 線上報名／光貿電子發票</footer>
</body>
</html>
