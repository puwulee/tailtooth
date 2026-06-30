<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            --bg: #0f172a; --panel: #ffffff; --muted: #64748b; --line: #e2e8f0;
            --brand: #4f46e5; --brand-d: #4338ca; --ok: #16a34a; --warn: #d97706;
            --danger: #dc2626; --pill: #eef2ff; --text: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: "Noto Sans TC", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9; color: var(--text); line-height: 1.6;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .topbar {
            background: var(--bg); color: #fff; padding: .85rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar a { color: #fff; }
        .topbar .brand { font-weight: 700; font-size: 1.1rem; }
        .container { max-width: 760px; margin: 0 auto; padding: 1.5rem 1rem 4rem; }
        .container.wide { max-width: 980px; }
        .panel {
            background: var(--panel); border: 1px solid var(--line); border-radius: 14px;
            padding: 1.25rem 1.35rem; box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .panel + .panel { margin-top: 1rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .25rem; }
        h2 { font-size: 1.15rem; margin: 0 0 .75rem; }
        .muted { color: var(--muted); }
        .row { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }
        .spread { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        label { display: block; font-weight: 600; margin: .9rem 0 .3rem; font-size: .92rem; }
        input[type=text], input[type=email], input[type=password], input[type=datetime-local], textarea, select {
            width: 100%; padding: .6rem .7rem; border: 1px solid var(--line); border-radius: 9px;
            font: inherit; background: #fff;
        }
        textarea { resize: vertical; min-height: 84px; }
        .check { display: flex; align-items: center; gap: .5rem; margin: .6rem 0; font-weight: 500; }
        .check input { width: auto; }
        .btn {
            display: inline-flex; align-items: center; gap: .4rem; cursor: pointer;
            background: var(--brand); color: #fff; border: 0; border-radius: 9px;
            padding: .6rem 1rem; font: inherit; font-weight: 600;
        }
        .btn:hover { background: var(--brand-d); text-decoration: none; }
        .btn.secondary { background: #fff; color: var(--text); border: 1px solid var(--line); }
        .btn.secondary:hover { background: #f8fafc; }
        .btn.ghost { background: transparent; color: var(--muted); padding: .4rem .6rem; }
        .btn.danger { background: var(--danger); }
        .btn.sm { padding: .35rem .6rem; font-size: .85rem; }
        .btn:disabled { opacity: .5; cursor: default; }
        .code-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .12em;
            background: var(--pill); color: var(--brand-d); padding: .35rem .7rem; border-radius: 8px;
            font-weight: 700; font-size: 1.05rem;
        }
        .pill { font-size: .78rem; padding: .15rem .55rem; border-radius: 999px; background: #f1f5f9; color: var(--muted); }
        .pill.open { background: #dcfce7; color: #166534; }
        .pill.closed { background: #fee2e2; color: #991b1b; }
        .pill.pending { background: #fef3c7; color: #92400e; }
        .alert { padding: .7rem .9rem; border-radius: 9px; margin-bottom: 1rem; font-size: .92rem; }
        .alert.ok { background: #dcfce7; color: #166534; }
        .alert.err { background: #fee2e2; color: #991b1b; }
        .errors { color: var(--danger); font-size: .88rem; margin-top: .3rem; }
        .empty { text-align: center; color: var(--muted); padding: 2rem 1rem; }
        .qcard { border: 1px solid var(--line); border-radius: 12px; padding: .85rem 1rem; margin-bottom: .7rem; background: #fff; }
        .qcard.pinned { border-color: #c7d2fe; background: #fbfbff; }
        .qcard .qbody { font-size: 1.02rem; white-space: pre-wrap; word-break: break-word; }
        .qcard .meta { color: var(--muted); font-size: .82rem; margin-top: .4rem; }
        .answer { margin-top: .7rem; padding: .6rem .8rem; background: #f8fafc; border-left: 3px solid var(--brand); border-radius: 6px; }
        .answer .lbl { font-weight: 700; color: var(--brand-d); font-size: .82rem; }
        .vote {
            display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 56px; padding: .35rem .5rem; border: 1px solid var(--line); border-radius: 10px;
            background: #fff; cursor: pointer; font: inherit; color: var(--text); line-height: 1.1;
        }
        .vote .n { font-weight: 700; font-size: 1.05rem; }
        .vote .t { font-size: .68rem; color: var(--muted); }
        .vote.voted { background: var(--brand); color: #fff; border-color: var(--brand); }
        .vote.voted .t { color: #e0e7ff; }
        .qrow { display: flex; gap: .8rem; align-items: flex-start; }
        .qrow .grow { flex: 1; min-width: 0; }
        .tabs { display: flex; gap: .4rem; margin-bottom: .9rem; }
        .tabs button { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: .35rem .9rem; cursor: pointer; font: inherit; }
        .tabs button.active { background: var(--brand); color: #fff; border-color: var(--brand); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .55rem .5rem; border-bottom: 1px solid var(--line); }
        .inline-form { display: inline; }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ url('/') }}" class="brand">💬 {{ config('app.name') }}</a>
        <div class="row">
            @auth
                <a href="{{ route('admin.events.index') }}">我的活動</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button class="btn ghost sm" type="submit">登出</button>
                </form>
            @else
                <a href="{{ route('login') }}">主辦者登入</a>
            @endauth
        </div>
    </div>

    <div class="container @yield('container-class')">
        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif
        @yield('content')
    </div>
</body>
</html>
